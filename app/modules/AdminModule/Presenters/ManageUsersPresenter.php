<?php

namespace App\Modules\AdminModule;

use App\Constants\UserProsecutionType;
use App\Core\AjaxRequestBuilder;
use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class ManageUsersPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageUsersPresenter', 'Users management');
     
        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });

        global $app;

        if(!$app->sidebarAuthorizator->canManageUsers($app->currentUser->getId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function actionLoadUsersGrid() {
        global $app;

        $page = $this->httpGet('gridPage');

        $gridSize = $app->cfg['GRID_SIZE'];

        $userCount = $app->userRepository->getUsersCount();
        $lastPage = ceil($userCount / $gridSize);
        $users = $app->userRepository->getUsersForGrid($gridSize, ($page * $gridSize));

        $gb = new GridBuilder();
        $gb->addColumns(['username' => 'Username', 'email' => 'Email', 'isAdmin' => 'Is administrator?']);
        $gb->addDataSource($users);
        $gb->addOnColumnRender('isAdmin', function(Cell $cell, UserEntity $entity) {
            if($entity->isAdmin()) {
                $cell->setValue('Yes');
                $cell->setTextColor('green');
            } else {
                $cell->setValue('No');
                $cell->setTextColor('red');
            }

            return $cell;
        });
        $gb->addAction(function (UserEntity $user) {
            return LinkBuilder::createSimpleLink('Profile', ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], 'grid-link');
        });
        $gb->addAction(function (UserEntity $user) use ($app) {
            if($user->getId() == $app->currentUser->getId()) {
                return '-';
            }

            if($user->isAdmin()) {
                return LinkBuilder::createSimpleLink('Unset as administrator', $this->createURL('unsetAdmin', ['userId' => $user->getId()]), 'grid-link');
            } else {
                return LinkBuilder::createSimpleLink('Set as administrator', $this->createURL('setAdmin', ['userId' => $user->getId()]), 'grid-link');
            }
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $userCount, 'getUsers');

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setMethod('GET')
            ->setURL(['page' => 'AdminModule:ManageUsers', 'action' => 'loadUsersGrid'])
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName('getUsers')
            ->setFunctionArguments(['_page'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getUsers(0)');
    }

    public function renderList() {
        $gridScript = $this->loadFromPresenterCache('gridScript');

        $this->template->grid_script = $gridScript;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $newUserLink = '<a class="post-data-link" href="?page=AdminModule:ManageUsers&action=newForm">New user</a>';
        $this->template->links = [$newUserLink];
    }

    public function handleUnsetAdmin(?FormResponse $fr = null) {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $password = $fr->password;

            try {
                $app->userAuth->authUser($password);
            } catch (AException $e) {
                $this->flashMessage('You entered bad credentials. Please try again.', 'error');
                $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'unsetAdmin', 'userId' => $userId]);
            }

            $app->userRepository->updateUser($userId, ['isAdmin' => '0']);
            $app->logger->warning('User #' . $userId . ' is not administrator. User #' . $app->currentUser->getId() . ' is responsible for this action.', __METHOD__);

            $cm = new CacheManager($app->logger);
            $cm->invalidateCache('users');

            $this->flashMessage('User ' . $user->getUsername() . ' is not an administrator.', 'info');
            $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'unsetAdmin', 'isSubmit' => '1', 'userId' => $userId])
                ->addPassword('password', 'Your password:', null, true)
                ->addSubmit('Unset user \'' . $user->getUsername() . '\' as administrator')
                ->addButton('Back', 'location.href = \'?page=AdminModule:ManageUsers&action=list\'');
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderUnsetAdmin() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleSetAdmin(?FormResponse $fr = null) {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $password = $fr->password;

            try {
                $app->userAuth->authUser($password);
            } catch (AException $e) {
                $this->flashMessage('You entered bad credentials. Please try again.', 'error');
                $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'setAdmin', 'userId' => $userId]);
            }

            $app->userRepository->updateUser($userId, ['isAdmin' => '1']);
            $app->logger->warning('User #' . $userId . ' is now administrator. User #' . $app->currentUser->getId() . ' is responsible for this action.', __METHOD__);

            $cm = new CacheManager($app->logger);
            $cm->invalidateCache('users');

            $this->flashMessage('User ' . $user->getUsername() . ' is now an administrator.', 'info');
            $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'setAdmin', 'isSubmit' => '1', 'userId' => $userId])
                ->addPassword('password', 'Your password:', null, true)
                ->addSubmit('Set user \'' . $user->getUsername() . '\' as administrator')
                ->addButton('Back', 'location.href = \'?page=AdminModule:ManageUsers&action=list\'')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderSetAdmin() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }
    
    public function handleWarnUser(?FormResponse $fr = null) {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $fr->description;

            $app->userProsecutionRepository->createNewProsecution($userId, UserProsecutionType::WARNING, $reason, null, null);

            $this->flashMessage('User \'' . $user->getUsername() . '\' has been warned.');
            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'warnUser', 'isSubmit' => '1', 'userId' => $userId])
                ->addTextArea('description', 'Reason:', null, true)
                ->addSubmit('Warn user \'' . $user->getUsername() .  '\'')
                ->addButton('Back', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderWarnUser() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleBanUser(?FormResponse $fr = null) {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $fr->description;
            $type = $fr->type;
            $startDate = $fr->startDate;
            $endDate = $fr->endDate;

            if($type == UserProsecutionType::PERMA_BAN) {
                try {
                    $app->userProsecutionManager->permaBanUser($userId, $app->currentUser->getId(), $reason);
                } catch(AException $e) {
                    $this->flashMessage('Could not ban user \'' . $user->getUsername() . '\'. Please try again.', 'error');
                    $this->redirect(['page' => 'AdminModule:FeedbacReports', 'action' => 'profile', 'reportId' => $reportId]);
                }
            } else {
                try {
                    $app->userProsecutionManager->banUser($userId, $app->currentUser->getId(), $reason, $startDate, $endDate);
                } catch(AException $e) {
                    $this->flashMessage('Could not ban user \'' . $user->getUsername() . '\'. Please try again.', 'error');
                    $this->redirect(['page' => 'AdminModule:FeedbacReports', 'action' => 'profile', 'reportId' => $reportId]);
                }
            }

            $this->flashMessage('User \'' . $user->getUsername() . '\' has been banned.');
            $this->redirect(['page' => 'AdminModule:FeedbacReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $date = new DateTime();

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'banUser', 'isSubmit' => '1', 'userId' => $userId])
                ->addTextArea('description', 'Reason:', null, true)
                ->addSelect('type', 'Type:', [['value' => UserProsecutionType::BAN, 'text' => 'Ban'], ['value' => UserProsecutionType::PERMA_BAN, 'text' => 'Perma ban']], true)
                ->addDatetime('startDate', 'Date from:', $date->getResult(), true)
                ->addDatetime('endDate', 'Date to:', $date->getResult(), true)
                ->addSubmit('Ban user \'' . $user->getUsername() .  '\'')
                ->addButton('Back', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'')
                ->addJSHandler('js/UserBanFormHandler.js')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderBanUser() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleNewForm(?FormResponse $fr = null) {
        global $app;

        if($this->httpGet('isSubmit') == '1') {
            $username = $fr->username;
            $password = $fr->password;
            $email = $fr->email;
            $isAdmin = $fr->evalBool($fr->isAdmin, 'on');

            if($email == '') {
                $email = null;
            }

            $password = HashManager::hashPassword($password);

            $app->userRepository->createNewUser($username, $password, $email, $isAdmin);

            $this->flashMessage('User <i>' . $username . '</i> has been created.', 'success');
            $this->redirect(['action' => 'list']);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'newForm', 'isSubmit' => '1'])
                ->addTextInput('username', 'Username:', null, true)
                ->addEmailInput('email', 'Email:', null, false)
                ->addPassword('password', 'Password:', null, true)
                ->addCheckbox('isAdmin', 'Administrator?')
                ->addSubmit('Create')
            ;

            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderNewForm() {
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->links = $links;
    }
}

?>