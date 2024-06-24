<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Constants\UserProsecutionType;
use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;

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

    public function handleList() {
        global $app;

        $gridScript = '<script type="text/javascript" src="js/UserGrid.js"></script><script type="text/javascript">getUsers(0, ' . $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('gridScript', $gridScript);
    }

    public function renderList() {
        $gridScript = $this->loadFromPresenterCache('gridScript');

        $this->template->grid_script = $gridScript;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $newUserLink = '<a class="post-data-link" href="?page=AdminModule:ManageUsers&action=newForm">New user</a>';
        $this->template->links = [$newUserLink];
    }

    public function handleUnsetAdmin() {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $password = $this->httpPost('password');

            try {
                $app->userAuth->authUser($password);
            } catch (AException $e) {
                $this->flashMessage('You entered bad credentials. Please try again.', 'error');
                $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'unsetAdmin', 'userId' => $userId]);
            }

            $app->userRepository->updateUser($userId, ['isAdmin' => '0']);
            $app->logger->warning('User #' . $userId . ' is not administrator. User #' . $app->currentUser->getId() . ' is responsible for this action.', __METHOD__);

            CacheManager::invalidateCache('users');

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

        $this->template->form = $form->render();
    }

    public function handleSetAdmin() {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $password = $this->httpPost('password');

            try {
                $app->userAuth->authUser($password);
            } catch (AException $e) {
                $this->flashMessage('You entered bad credentials. Please try again.', 'error');
                $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'setAdmin', 'userId' => $userId]);
            }

            $app->userRepository->updateUser($userId, ['isAdmin' => '1']);
            $app->logger->warning('User #' . $userId . ' is now administrator. User #' . $app->currentUser->getId() . ' is responsible for this action.', __METHOD__);

            CacheManager::invalidateCache('users');

            $this->flashMessage('User ' . $user->getUsername() . ' is now an administrator.', 'info');
            $this->redirect(['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'setAdmin', 'isSubmit' => '1', 'userId' => $userId])
                ->addPassword('password', 'Your password:', null, true)
                ->addSubmit('Set user \'' . $user->getUsername() . '\' as administrator')
                ->addButton('Back', 'location.href = \'?page=AdminModule:ManageUsers&action=list\'');
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderSetAdmin() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }
    
    public function handleWarnUser() {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $this->httpPost('description');

            $app->userProsecutionRepository->createNewProsecution($userId, UserProsecutionType::WARNING, $reason, null, null);

            $this->flashMessage('User \'' . $user->getUsername() . '\' has been warned.');
            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'warnUser', 'isSubmit' => '1', 'userId' => $userId])
                ->addTextArea('description', 'Reason:', null, true)
                ->addSubmit('Warn user \'' . $user->getUsername() .  '\'');
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderWarnUser() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }

    public function handleBanUser() {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $this->httpPost('description');
            $type = $this->httpPost('type');
            $startDate = $this->httpPost('startDate');
            $endDate = $this->httpPost('endDate');

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
                ->addJSHandler('js/UserBanFormHandler.js')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderBanUser() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }

    public function handleNewForm() {
        global $app;

        if($this->httpGet('isSubmit') == '1') {
            $username = $this->httpPost('username');
            $password = $this->httpPost('password');
            $email = $this->httpPost('email');
            $isAdmin = $this->httpPost('isAdmin') == 'on';

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
        }
    }

    public function renderNewForm() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }
}

?>