<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Constants\UserProsecutionType;
use App\Exceptions\AException;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class ManageUsersPresenter extends APresenter {
    public function __construct() {
        parent::__construct('ManageUsersPresenter', 'Users management');
     
        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        $sb->addLink('Users', ['page' => 'AdminModule:ManageUsers', 'action' => 'list'], true);
        $sb->addLink('User prosecution', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
        $sb->addLink('System status', ['page' => 'AdminModule:ManageSystemStatus', 'action' => 'list']);

        return $sb->render();
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
        $this->template->links = [''];
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

            $app->userProsecutionRepository->createNewProsecution($userId, $type, $reason, $startDate, $endDate);

            $this->flashMessage('User \'' . $user->getUsername() . '\' has been banned.');
            $this->redirect(['page' => 'AdminModule:FeedbacReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUsers', 'action' => 'banUser', 'isSubmit' => '1', 'userId' => $userId])
                ->addTextArea('description', 'Reason:', null, true)
                ->addSelect('type', 'Type:', [['value' => UserProsecutionType::BAN, 'text' => 'Ban'], ['value' => UserProsecutionType::PERMA_BAN, 'text' => 'Perma ban']], true)
                ->addDatetime('startDate', 'Date from:', date('Y-m-d H:i:s'), true)
                ->addDatetime('endDate', 'Date to:', date('Y-m-d H:i:s'), true)
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
}

?>