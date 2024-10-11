<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Core\Caching\CacheNames;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\FormBuilder\Option;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageGroupsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageGroupsPresenter', 'Group management');
    }

    public function startup() {
        parent::startup();
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->groupRepository->composeQueryForGroups(), 'groupId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnText('description', 'Description');

        $action = $grid->addAction('members');
        $action->onCanRender[] = function() {
            return true;
        };
        $action->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            return LinkBuilder::createSimpleLink('Members', $this->createURL('listMembers', ['groupId' => $primaryKey]), 'grid-link');
        };

        return $grid;
    }

    public function renderList() {
        $this->template->links = [];
    }

    public function createComponentGridMembers(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->groupRepository->composeQueryForGroupMembers($request->query['groupId']), 'membershipId');

        $grid->addColumnUser('userId', 'User');
        $grid->addColumnDatetime('dateCreated', 'Member since');

        $remove = $grid->addAction('remove');
        $remove->setTitle('Remove');
        $remove->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if($this->app->actionAuthorizator->canRemoveMemberFromGroup($this->getUserId()) && ($row->userId != $this->getUserId())) {
                return true;
            } else {
                return false;
            }
        };
        $remove->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            return LinkBuilder::createSimpleLink('Remove', ['page' => 'AdminModule:ManageGroups', 'action' => 'removeMember', 'groupId' => $row->groupId, 'userId' => $primaryKey], 'grid-link');
        };

        return $grid;
    }

    public function handleListMembers() {
        $groupId = $this->httpGet('groupId', true);
        $group = $this->app->groupRepository->getGroupById($groupId);

        $this->saveToPresenterCache('group', $group);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'post-data-link') . "&nbsp;"
        ];

        if($this->app->actionAuthorizator->canAddMemberToGroup($this->getUserId())) {
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
        $groupId = $this->httpGet('groupId', true);
        $group = $this->app->groupRepository->getGroupById($groupId);

        if($this->httpGet('isSubmit') == '1') {
            $user = $fr->user;
            
            try {
                $userEntity = $this->app->userManager->getUserById($user);

                $this->app->groupRepository->beginTransaction();

                $this->app->groupRepository->addGroupMember($groupId, $user);

                $this->app->groupRepository->commit($this->getUserId(), __METHOD__);

                $cache = $this->cacheFactory->getCache(CacheNames::GROUP_MEMBERSHIPS);
                $cache->invalidate();
                
                $this->flashMessage('User <i>' . $userEntity->getUsername() . '</i> has been added to group <i>' . $group->getTitle() . '</i>', 'success');
            } catch(AException $e) {
                $this->app->groupRepository->rollback();

                $this->flashMessage('Could not added user to the group. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['action' => 'listMembers', 'groupId' => $groupId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageGroups', 'action' => 'newMember', 'isSubmit' => '1', 'groupId' => $groupId])
                ->addTextInput('usernameSearch', 'Username:', null, true)
                ->addButton('Search', 'searchUsers(' . $this->getUserId() . ', ' . $groupId . ')')
                ->addSelect('user', 'User:', [], true)
                ->addSubmit('Add user', false, true)
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

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('listMembers', ['groupId' => $groupId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function actionSearchUsersForNewMemberForm() {
        $username = $this->httpGet('q');
        $groupId = $this->httpGet('groupId');

        $groupMembers = $this->app->groupRepository->getGroupMemberUserIds($groupId);

        $qb = $this->app->userRepository->composeStandardQuery($username, __METHOD__);
        $qb ->andWhere($qb->getColumnNotInValues('userId', $groupMembers))
            ->andWhere('isAdmin = 1')
        ;

        $users = $this->app->userRepository->getUsersFromQb($qb);

        $options = [];
        foreach($users as $user) {
            $option = new Option($user->getId(), $user->getUsername());

            $options[] = $option->render();
        }

        return ['users' => $options, 'count' => count($options)];
    }

    public function renderNewMember() {
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->links = $links;
    }

    public function handleRemoveMember(?FormResponse $fr = null) {
        $groupId = $this->httpGet('groupId');
        $group = $this->app->groupRepository->getGroupById($groupId);
        
        $userId = $this->httpGet('userId');
        try {
            $user = $this->app->userManager->getUserById($userId);
        } catch(AException $e) {
            $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect($this->createURL('listMembers', ['groupId' => $groupId]));
        }

        if($this->httpGet('isSubmit') == '1') {
            try {
                $this->app->groupRepository->beginTransaction();

                $this->app->groupRepository->removeGroupMember($groupId, $userId);

                $this->app->groupRepository->commit($this->getUserId(), __METHOD__);

                $cache = $this->cacheFactory->getCache(CacheNames::GROUP_MEMBERSHIPS);
                $cache->invalidate();

                $this->flashMessage('Removed user <i>' . $user->getUsername() . '</i> from group <i>' . $group->getTitle() . '</i>.', 'success');
            } catch(AException $e) {
                $this->app->groupRepository->rollback();

                $this->flashMessage('Could not remove user from the group. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['action' => 'listMembers', 'groupId' => $groupId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageGroups', 'action' => 'removeMember', 'isSubmit' => '1', 'groupId' => $groupId, 'userId' => $userId])
                ->addSubmit('Remove user \'' . $user->getUsername() . ' from group \'' . $group->getTitle() . '\'')
                ->addButton('&larr; Go back', 'location.href = \'?page=AdminModule:ManageGroups&action=listMembers&groupId=' . $groupId . '\';', 'formSubmit')
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