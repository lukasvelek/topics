<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Core\CacheManager;
use App\Entities\GroupEntity;
use App\Entities\GroupMembershipEntity;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\FormBuilder\Option;
use App\UI\GridBuilder\Cell;
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

        $gridSize = $app->cfg['GRID_SIZE'];

        $totalCount = $app->groupRepository->getGroupCount();
        $lastPage = ceil($totalCount / $gridSize);
        $groups = $app->groupRepository->getGroupsForGrid($gridSize, ($page * $gridSize));

        $gb = new GridBuilder();
        $gb->addColumns(['title' => 'Title', 'description' => 'Description']);
        $gb->addDataSource($groups);
        $gb->addAction(function(GroupEntity $entity) {
            return LinkBuilder::createSimpleLink('Members', ['page' => 'AdminModule:ManageGroups', 'action' => 'listMembers', 'groupId' => $entity->getId()], 'grid-link');
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getGroupGrid');

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'AdminModule:ManageGroups', 'action' => 'loadGroupGrid'])
            ->setMethod('GET')
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName('getGroupGrid')
            ->setFunctionArguments(['_page'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getGroupGrid(0)');
    }

    public function renderList() {
        $grid = $this->loadFromPresenterCache('grid');

        $this->template->grid_script = $grid;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $this->template->links = [];
    }

    public function actionGroupMemberGrid() {
        global $app;

        $page = $this->httpGet('gridPage');
        $groupId = $this->httpGet('groupId');

        $gridSize = $app->cfg['GRID_SIZE'];

        $membersCount = $app->groupRepository->getGroupMembersCount($groupId);
        $lastPage = ceil($membersCount / $gridSize);
        $members = $app->groupRepository->getGroupMembersForGrid($groupId, $gridSize, ($page * $gridSize));
        $users = [];

        foreach($members as $member) {
            $users[$member->getUserId()] = $app->userRepository->getUserById($member->getUserId());
        }

        $gb = new GridBuilder();
        $gb->addColumns(['user' => 'User', 'dateCreated' => 'Member since']);
        $gb->addDataSource($members);
        $gb->addOnColumnRender('user', function(Cell $cell, GroupMembershipEntity $entity) use ($users) {
            $user = $users[$entity->getUserId()];
            return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], 'grid-link');
        });
        $gb->addOnColumnRender('dateCreated', function(Cell $cell, GroupMembershipEntity $entity) {
            return DateTimeFormatHelper::formatDateToUserFriendly($entity->getDateCreated());
        });
        $gb->addAction(function(GroupMembershipEntity $entity) use ($app) {
            if($app->actionAuthorizator->canRemoveMemberFromGroup($app->currentUser->getId()) && $entity->getUserId() != $app->currentUser->getId()) {
                return LinkBuilder::createSimpleLink('Remove', ['page' => 'AdminModule:ManageGroups', 'action' => 'removeMember', 'groupId' => $entity->getGroupId(), 'userId' => $entity->getUserId()], 'grid-link');
            } else {
                return '-';
            }
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $membersCount, 'getGroupMembersGrid', [$groupId]);

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleListMembers() {
        global $app;

        $groupId = $this->httpGet('groupId', true);
        $group = $app->groupRepository->getGroupById($groupId);

        $this->saveToPresenterCache('group', $group);

        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'AdminModule:ManageGroups', 'action' => 'groupMemberGrid'])
            ->setMethod('get')
            ->setHeader(['gridPage' => '_page', 'groupId' => '_groupId'])
            ->setFunctionName('getGroupMembersGrid')
            ->setFunctionArguments(['_page', '_groupId'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getGroupMembersGrid(0, ' . $groupId . ')');

        $links = [];

        if($app->actionAuthorizator->canAddMemberToGroup($app->currentUser->getId())) {
            $links[] = LinkBuilder::createSimpleLink('Add member', ['page' => 'AdminModule:ManageGroups', 'action' => 'newMember', 'groupId' => $groupId], 'post-data-link');
        }

        $this->saveToPresenterCache('links', $links);
    }

    public function renderListMembers() {
        $links = $this->loadFromPresenterCache('links');
        $group = $this->loadFromPresenterCache('group');

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

            $cm = new CacheManager($app->logger);
            $cm->invalidateCache('groupMemberships');

            $this->flashMessage('User <i>' . $userEntity->getUsername() . '</i> has been added to group <i>' . $group->getTitle() . '</i>', 'success');
            $this->redirect(['action' => 'listMembers', 'groupId' => $groupId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageGroups', 'action' => 'newMember', 'isSubmit' => '1', 'groupId' => $groupId])
                ->addTextInput('usernameSearch', 'Username:', null, true)
                ->addButton('Search', 'searchUsers(' . $app->currentUser->getId() . ', ' . $groupId . ')')
                ->addSelect('user', 'User:', [], true)
                ->addSubmit('Add user')
            ;

            $this->saveToPresenterCache('form', $fb);

            $arb = new AjaxRequestBuilder();

            $arb->setURL(['page' => 'AdminModule:ManageGroups', 'action' => 'searchUsersForNewMemberForm'])
                ->setMethod('GET')
                ->setHeader(['groupId' => '_groupId', 'q' => '_q'])
                ->setFunctionName('searchUsers')
                ->setFunctionArguments(['_groupId'])
                ->addWhenDoneOperation('if(obj.count == 0) { alert("No users found"); }')
                ->updateHTMLElement('user', 'users')
                ->addCustomArg('_q')
                ->addBeforeAjaxOperation('const _q = $("#usernameSearch").val();');
            ;

            $this->addScript($arb->build());
        }
    }

    public function actionSearchUsersForNewMemberForm() {
        global $app;
        
        $username = $this->httpGet('q');
        $groupId = $this->httpGet('groupId');

        $groupMembers = $app->groupRepository->getGroupMemberUserIds($groupId);

        $qb = $app->userRepository->composeStandardQuery($username, __METHOD__);
        $qb ->andWhere($qb->getColumnNotInValues('userId', $groupMembers))
            ->andWhere('isAdmin = 1')
        ;

        $users = $app->userRepository->getUsersFromQb($qb);

        $options = [];
        foreach($users as $user) {
            $option = new Option($user->getId(), $user->getUsername());

            $options[] = $option->render();
        }

        $this->ajaxSendResponse(['users' => $options, 'count' => count($options)]);
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

            $cm = new CacheManager($app->logger);
            $cm->invalidateCache('groupMemberships');

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