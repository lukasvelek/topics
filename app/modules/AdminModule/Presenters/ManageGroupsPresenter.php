<?php

namespace App\Modules\AdminModule;

use App\Core\CacheManager;
use App\Entities\GroupEntity;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class ManageGroupsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageGroupsPresenter', 'Group management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function actionLoadGroupGrid() {
        global $app;

        $page = $this->httpGet('gridPage');

        $elementsOnPage = $app->cfg['GRID_SIZE'];

        $count = $app->groupRepository->getGroupCount();
        $lastPage = ceil($count / $elementsOnPage) - 1;
        $groups = $app->groupRepository->getGroupsForGrid($elementsOnPage, ($page * $elementsOnPage));

        $gb = new GridBuilder();
        $gb->addColumns(['title' => 'Title', 'description' => 'Description']);
        $gb->addDataSource($groups);
        $gb->addAction(function(GroupEntity $entity) {
            return LinkBuilder::createSimpleLink('Members', ['page' => 'AdminModule:ManageGroups', 'action' => 'listMembers', 'groupId' => $entity->getId()], 'post-data-link');
        });

        $paginator = $gb->createGridControls2('getGroups', $page, $lastPage);

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator]);
    }

    public function handleList() {
        $this->ajaxMethod('getGroups', ['_page'], ['page' => 'AdminModule:ManageGroups', 'action' => 'loadGroupGrid'], 'get', ['gridPage' => '$_page'], $this->ajaxUpdateElements(['grid-content' => 'grid', 'grid-paginator' => 'paginator']));
        $this->addScript('getGroups(0);');
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

    public function handleNewMember(?FormResponse $fr = null) {
        global $app;

        $groupId = $this->httpGet('groupId', true);
        $group = $app->groupRepository->getGroupById($groupId);

        if($this->httpGet('isSubmit') == '1') {
            $user = $fr->user;
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

        $this->template->form = $form;
    }

    public function handleRemoveMember(?FormResponse $fr = null) {
        global $app;

        $groupId = $this->httpGet('groupId');
        $group = $app->groupRepository->getGroupById($groupId);
        
        $userId = $this->httpGet('userId');
        $user = $app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') == '1') {
            $app->groupRepository->removeGroupMember($groupId, $userId);

            CacheManager::invalidateCache('groupMemberships');

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

        $this->template->form = $form;
    }
}

?>