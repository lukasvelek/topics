<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;

class ManageUserProsecutionsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageUserProsecutionsPresenter', 'Manage user prosecutions');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });

        global $app;

        if(!$app->sidebarAuthorizator->canManageUserProsecutions($app->currentUser->getId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }
    
    public function handleList() {
        global $app;

        $gridScript = '<script type="text/javascript">getUserProsecutions(0, ' . $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('gridScript', $gridScript);

        $links = [
            LinkBuilder::createSimpleLink('Prosecution log', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'logList'], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $gridScript = $this->loadFromPresenterCache('gridScript');
        $links = $this->loadFromPresenterCache('links');

        $this->template->grid_script = $gridScript;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $this->template->links = $links;
    }

    public function handleRemoveProsecution(?FormResponse $fr = null) {
        global $app;

        $prosecutionId = $this->httpGet('prosecutionId', true);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $fr->reason;
            $password = $fr->password;

            try {
                $app->userAuth->authUser($password);
            } catch(AException $e) {
                $this->flashMessage('Could not authenticate user. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
            }

            $prosecution = $app->userProsecutionRepository->getProsecutionById($prosecutionId);
            $user = $app->userRepository->getUserById($prosecution->getUserId());

            try {
                $app->userProsecutionManager->removeBan($prosecution->getUserId(), $app->currentUser->getId(), $reason);
            } catch(AException $e) {
                $this->flashMessage('Could not remove ban for user \'' . $user->getUsername() . '\' (' . $user->getId() . '). Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
            }

            $this->flashMessage('Removed ban for user \'' . $user->getUsername() . '\' (' . $user->getId() . ').');
            $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'removeProsecution', 'isSubmit' => '1', 'prosecutionId' => $prosecutionId])
                ->addTextArea('reason', 'Reason:', null, true)
                ->addPassword('password', 'Password:', null, true)
                ->addPassword('passwordCheck', 'Password again:', null, true)
                ->addSubmit('Remove ban')
                ->addJSHandler('js/UserUnbanFormHandler.js')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderRemoveProsecution() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleLogList() {
        global $app;

        $gridScript = '<script type="text/javascript">getUserProsecutionLog(0, ' . $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('gridScript', $gridScript);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list'], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderLogList() {
        $gridScript = $this->loadFromPresenterCache('gridScript');
        $links = $this->loadFromPresenterCache('links');

        $this->template->grid_script = $gridScript;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $this->template->links = $links;
    }
}

?>