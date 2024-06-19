<?php

namespace App\Modules\AdminModule;

use App\Core\CacheManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\LinkBuilder;

class ManageGroupsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageGroupsPresenter', 'Group management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function handleList() {
        global $app;

        $script = '<script type="text/javascript">getGroups(0, '. $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('grid', $script);
    }

    public function renderList() {
        $grid = $this->loadFromPresenterCache('grid');

        $this->template->grid_script = $grid;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $this->template->links = [];
    }

    public function handleListMembers() {
        global $app;

        $groupId = $this->httpGet('groupId', true);
        $group = $app->groupRepository->getGroupById($groupId);

        $this->saveToPresenterCache('group', $group);

        $script = '<script type="text/javascript">getGroupMembers(0, ' . $groupId . ', '. $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('grid', $script);

        $links = [];

        if($app->actionAuthorizator->canAddMemberToGroup($app->currentUser->getId())) {
            $links[] = LinkBuilder::createSimpleLink('Add member', ['page' => 'AdminModule:ManageGroups', 'action' => 'newMember', 'groupId' => $groupId], 'post-data-link');
        }

        $this->saveToPresenterCache('links', $links);
    }

    public function renderListMembers() {
        $grid = $this->loadFromPresenterCache('grid');
        $links = $this->loadFromPresenterCache('links');
        $group = $this->loadFromPresenterCache('group');

        $this->template->grid_script = $grid;
        $this->template->grid = '';
        $this->template->grid_paginator = '';
        $this->template->links = $links;
        $this->template->group_title = $group->getTitle();
    }

    public function handleNewMember() {
        global $app;

        $groupId = $this->httpGet('groupId', true);
        $group = $app->groupRepository->getGroupById($groupId);

        if($this->httpGet('isSubmit') == '1') {
            $user = $this->httpPost('user');
            $userEntity = $app->userRepository->getUserById($user);

            $app->groupRepository->addGroupMember($groupId, $user);

            CacheManager::invalidateCache('groupMemberships');

            $this->flashMessage('User <i>' . $userEntity->getUsername() . '</i> has been added to group <i>' . $group->getTitle() . '</i>', 'success');
            $this->redirect(['action' => 'listMembers', 'groupId' => $groupId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageGroups', 'action' => 'newMember', 'isSubmit' => '1', 'groupId' => $groupId])
                ->addJSHandler('js/NewGroupMemberFormHandler.js')
                ->addTextInput('usernameSearch', 'Username:', null, true)
                ->addButton('Search', 'searchUsers(' . $app->currentUser->getId() . ', ' . $groupId . ')')
                ->addSelect('user', 'User:', [], true)
                ->addSubmit('Add user')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderNewMember() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }

    public function handleRemoveMember() {
        global $app;

        $groupId = $this->httpGet('groupId');
        $group = $app->groupRepository->getGroupById($groupId);
        
        $userId = $this->httpGet('userId');
        $user = $app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') == '1') {
            $app->groupRepository->removeGroupMember($groupId, $userId);

            $this->flashMessage('Removed user <i>' . $user->getUsername() . '</i> from group <i>' . $group->getTitle() . '</i>.', 'success');
            $this->redirect(['action' => 'listMembers', 'groupId' => $groupId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageGroups', 'action' => 'removeMember', 'isSubmit' => '1', 'groupId' => $groupId, 'userId' => $userId])
                ->addSubmit('Remove user \'' . $user->getUsername() . ' from group \'' . $group->getTitle() . '\'')
                ->addButton('&larr; Go back', 'location.href = \'?page=AdminModule:ManageGroups&action=listMembers&groupId=' . $groupId . '\';')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderRemoveMember() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }
}

?>