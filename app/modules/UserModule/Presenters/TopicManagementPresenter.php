<?php

namespace App\Modules\UserModule;

use App\Constants\TopicMemberRole;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;
use Exception;

class TopicManagementPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicManagementPresenter', 'Topic management');
    }
    
    public function startup() {
        parent::startup();
    }

    public function handleManageRoles() {
        $topicId = $this->httpGet('topicId');

        try {
            $topic = $this->app->topicManager->getTopicById($topicId, $this->getUserId());
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
        }

        $this->saveToPresenterCache('title', $topic->getTitle());

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderManageRoles() {
        $title = $this->loadFromPresenterCache('title');
        $links = $this->loadFromPresenterCache('links');

        $this->template->topic_title = $title;
        $this->template->links = $links;
    }

    public function createComponentRolesGrid(HttpRequest $request) {
        $topicId = $request->query['topicId'];

        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->topicMembershipManager->composeQueryForTopicMembers($topicId), 'membershipId');

        $grid->addQueryDependency('topicId', $topicId);

        $grid->addColumnUser('userId', 'User');
        $col = $grid->addColumnText('role', 'Role');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span')
                    ->text(TopicMemberRole::toString($value))
                    ->style('color', TopicMemberRole::getColorByKey($value));

            return $el;
        };
        $grid->addColumnDatetime('dateCreated', 'Member since');

        $changeRole = $grid->addAction('changeRole');
        $changeRole->setTitle('Change role');
        $changeRole->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($topicId) {
            return $this->app->actionAuthorizator->canChangeUserTopicRole($topicId, $this->getUserId(), $row->userId);
        };
        $changeRole->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($topicId) {
            $el = HTML::el('a')
                    ->class('grid-link')
                    ->text('Change role')
                    ->href($this->createURLString('changeRoleForm', ['topicId' => $topicId, 'userId' => $row->userId]));

            return $el;
        };

        $grid->enableExport();

        return $grid;
    }

    public function handleChangeRoleForm() {
        $topicId = $this->httpGet('topicId');
        $userId = $this->httpGet('userId');
        
        if(!$this->app->actionAuthorizator->canChangeUserTopicRole($topicId, $this->getUserId(), $userId)) {
            $this->flashMessage('You are not authorized to change role of the selected user in this topic.', 'error');
            $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
        }

        if($this->httpGet('isSubmit') == '1') {
            $role = $this->httpPost('role');

            $oldRole = $this->app->topicMembershipManager->getFollowRole($topicId, $userId);
            $oldRole = '<span style="color: ' . TopicMemberRole::getColorByKey($oldRole) . '">' . TopicMemberRole::toString($oldRole) . '</span>';

            $newRole = '<span style="color: ' . TopicMemberRole::getColorByKey($role) . '">' . TopicMemberRole::toString($role) . '</span>';

            try {
                $topic = $this->app->topicManager->getTopicById($topicId, $this->getUserId());
            } catch(AException $e) {
                $this->flashMessage('Could not change role of the selected user. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
            }

            try {
                $this->app->topicMembershipRepository->beginTransaction();

                $this->app->topicMembershipManager->changeRole($topicId, $userId, $this->getUserId(), $role);

                $this->app->notificationManager->createNewTopicRoleChangedNotification($userId, LinkBuilder::createSimpleLinkObject($topic->getTitle(), ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link'), $oldRole, $newRole);

                $this->app->topicMembershipRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Role of the selected user changed.', 'success');
            } catch(AException $e) {
                $this->app->topicMembershipRepository->rollback();
                $this->flashMessage('Could not change role of the selected user. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
        } else {
            try {
                $topic = $this->app->topicManager->getTopicById($topicId, $this->getUserId());
            } catch(AException $e) {
                $this->flashMessage($e->getMessage(), 'error');
                $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
            }

            $this->saveToPresenterCache('topic', $topic);

            $memberRole = $this->app->topicMembershipManager->getFollowRole($topicId, $userId);

            $roleArray = TopicMemberRole::getAll();

            $roles = [];
            foreach($roleArray as $k => $v) {
                if($k == TopicMemberRole::OWNER) continue;

                $tmp = [
                    'value' => $k,
                    'text' => $v
                ];

                if($k == $memberRole) {
                    $tmp['selected'] = 'selected';
                }

                $roles[] = $tmp;
            }

            $fb = new FormBuilder();
            
            $fb->setAction(['page' => 'UserModule:TopicManagement', 'action' => 'changeRoleForm', 'isSubmit' => 1, 'topicId' => $topicId, 'userId' => $userId])
                ->addSelect('role', 'Role:', $roles, true)
                ->addSubmit('Save')
            ;

            $this->saveToPresenterCache('form', $fb);
        }

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderChangeRoleForm() {
        $form = $this->loadFromPresenterCache('form');
        $topic = $this->loadFromPresenterCache('topic');
        $links = $this->loadFromPresenterCache('links');
        
        $this->template->form = $form;
        $this->template->topic_title = $topic->getTitle();
        $this->template->links = $links;
    }

    public function handleListPolls() {
        $topicId = $this->httpGet('topicId');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderListPolls() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    protected function createComponentPollsGrid(HttpRequest $request) {
        $topicId = $request->query['topicId'];

        $grid = $this->getGridBuilder();
        $grid->addQueryDependency('topicId', $topicId);

        if($this->app->actionAuthorizator->canSeeAllTopicPolls($this->getUserId(), $topicId)) {
            $grid->createDataSourceFromQueryBuilder($this->app->topicPollRepository->composeQueryForTopicPolls($topicId), 'pollId');
        } else {
            $grid->createDataSourceFromQueryBuilder($this->app->topicPollRepository->composeQueryForMyTopicPolls($topicId, $this->getUserId()), 'pollId');
        }

        $grid->addColumnUser('authorId', 'Author');
        $grid->addColumnText('title', 'Title');

        $col = $grid->addColumnBoolean('status', 'Status');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            if((isset($row->dateValid) && $row->dateValid === null) || !isset($row->dateValid) || strtotime($row->dateValid) > time()) {
                $el->style('color', 'green')
                    ->text('Active');
            } else {
                $el->style('color', 'red')
                    ->text('Inactive');
            }

            return $el;
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
            if((isset($row->dateValid) && $row->dateValid === null) || !isset($row->dateValid) || strtotime($row->dateValid) > time()) {
                return 'Active';
            } else {
                return 'Inactive';
            }
        };
        
        $grid->addColumnDatetime('dateCreated', 'Date created');
        $grid->addColumnDatetime('dateValid', 'Date valid');
        $col = $grid->addColumnText('votes', 'Votes');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span');

            $votes = $this->app->topicPollRepository->getPollResponses($row->pollId);

            $el->text(count($votes));

            return $el;
        };

        $analytics = $grid->addAction('analytics');
        $analytics->setTitle('Analytics');
        $analytics->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($topicId) {
            return $this->app->actionAuthorizator->canSeePollAnalytics($this->getUserId(), $topicId, $row->authorId);
        };
        $analytics->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($topicId) {
            $el = HTML::el('a')
                    ->class('grid-link')
                    ->text('Analytics')
                    ->href($this->createFullURLString('UserModule:Topics', 'pollAnalytics', ['pollId' => $primaryKey, 'backPage' => 'UserModule:TopicManagement', 'backAction' => 'listPolls', 'topicId' => $topicId]));

            return $el;
        };

        $activation = $grid->addAction('activation');
        $activation->setTitle('Reactivate / Deactivate');
        $activation->onCanRender[] = function(DatabaseRow $row, Row $_row) use ($topicId) {
            return $this->app->actionAuthorizator->canDeactivePoll($this->getUserId(), $topicId, $row->authorId);
        };
        $activation->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($topicId) {
            $el = HTML::el('a');
            $el->class('grid-link');

            if((isset($row->dateValid) && $row->dateValid === null) || !isset($row->dateValid) || strtotime($row->dateValid) > time()) {
                $el->href($this->createURLString('deactivatePoll', ['pollId' => $primaryKey, 'topicId' => $topicId]))
                    ->text('Deactivate');
            } else {
                $el->href($this->createURLString('reactivatePoll', ['pollId' => $primaryKey, 'topicId' => $topicId]))
                    ->text('Reactivate for 24 hrs');
            }

            return $el;
        };

        return $grid;
    }

    public function handleDeactivatePoll() {
        $pollId = $this->httpGet('pollId');
        $topicId = $this->httpGet('topicId');

        try {
            $this->app->topicPollRepository->beginTransaction();

            $this->app->topicPollRepository->closePoll($pollId);

            $this->app->topicPollRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Poll deactivated.', 'success');
        } catch(AException $e) {
            $this->app->topicPollRepository->rollback();

            $this->flashMessage('Poll could not be deactivated. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listPolls', 'topicId' => $topicId]);
    }

    public function handleReactivatePoll() {
        $pollId = $this->httpGet('pollId');
        $topicId = $this->httpGet('topicId');

        $tomorrow = new DateTime();
        $tomorrow->modify('+1d');
        $tomorrow = $tomorrow->getResult();

        try {
            $this->app->topicPollRepository->beginTransaction();

            $this->app->topicPollRepository->openPoll($pollId, $tomorrow);

            $this->app->topicPollRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Poll reactivated.', 'success');
        } catch(AException $e) {
            $this->app->topicPollRepository->rollback();

            $this->flashMessage('Poll could not be reactivated. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listPolls', 'topicId' => $topicId]);
    }

    public function handleListInvites() {
        $topicId = $this->httpGet('topicId', true);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link') . '&nbsp;',
            LinkBuilder::createSimpleLink('New invite', ['page' => 'UserModule:TopicManagement', 'action' => 'inviteForm', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderListInvites() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    protected function createComponentInvitesGrid(HttpRequest $request) {
        $topicId = $request->query['topicId'];

        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->topicInviteRepository->composeQueryForInvitesForTopic($topicId), 'inviteId');
        $grid->addQueryDependency('topicId', $topicId);

        $grid->addColumnUser('userId', 'User');
        $grid->addColumnDatetime('dateValid', 'Valid until');
        
        $remove = $grid->addAction('remove');
        $remove->setTitle('Remove');
        $remove->onCanRender[] = function() {
            return true;
        };
        $remove->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) use ($topicId) {
            $el = HTML::el('a')
                    ->class('grid-link')
                    ->text('Remove')
                    ->href($this->createFullURLString('UserModule:TopicManagement', 'removeInvite', ['topicId' => $topicId, 'userId' => $row->userId]));

            return $el;
        };

        return $grid;
    }

    public function handleInviteForm() {
        $topicId = $this->httpGet('topicId');

        if($this->httpGet('isFormSubmit') == '1') {
            $userId = $this->httpPost('userSelect');

            try {
                $this->app->topicMembershipRepository->beginTransaction();

                $this->app->topicMembershipManager->inviteUser($topicId, $userId, $this->getUserId());

                $this->app->topicMembershipRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User invited.', 'success');
            } catch(AException $e) {
                $this->app->topicMembershipRepository->rollback();

                $this->flashMessage('Could not invite user. Reason: ' . $e->getMessage(), 'error');
            }
            
            $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listInvites', 'topicId' => $topicId]);
        } else {
            $links = [];
            $this->saveToPresenterCache('links', $links);

            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'UserModule:TopicManagement', 'action' => 'inviteForm', 'topicId' => $topicId])
                ->addTextInput('username', 'Username:', null, true)
                ->addButton('Search...', 'searchUser()')
                ->addSelect('userSelect', 'User:', [], true)
                ->addSubmit('Invite')
                ->addJSHandler('js/TopicInviteFormHandler.js')
                ->addHidden('topicId', $topicId)
            ;
            
            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderInviteForm() {
        $links = $this->loadFromPresenterCache('links');
        $form = $this->loadFromPresenterCache('form');

        $this->template->links = $links;
        $this->template->form = $form;
    }

    public function actionSearchUser() {
        $username = $this->httpGet('query');
        $topicId = $this->httpGet('topicId');

        $users = $this->app->userRepository->searchUsersByUsername($username);

        $invites = $this->app->topicMembershipManager->getInvitesForTopic($topicId);

        $checkInvite = function(string $userId) use ($invites) {
            $result = false;
            foreach($invites as $invite) {
                if($invite->getUserId() == $userId) {
                    $result = true;
                    break;
                }
            }
            return $result;
        };

        $members = $this->app->topicMembershipManager->getTopicMembers($topicId, 0, 0, false);

        $checkMembership = function(string $userId) use ($members) {
            $result = false;
            foreach($members as $member) {
                if($member->getUserId() == $userId) {
                    $result = true;
                    break;
                }
            }
            return $result;
        };

        $usersOptions = [];
        foreach($users as $user) {
            if($checkMembership($user->getId())) {
                continue;
            }
            if($checkInvite($user->getId())) {
                continue;
            }

            $usersOptions[] = '<option value="' . $user->getId() . '">' . $user->getUsername() . '</option>';
        }

        return ['users' => $usersOptions, 'empty' => empty($usersOptions)];
    }

    public function handleRemoveInvite() {
        $userId = $this->httpGet('userId');
        $topicId = $this->httpGet('topicId');

        try {
            $this->app->topicMembershipRepository->beginTransaction();

            $this->app->topicMembershipManager->removeInvite($topicId, $userId);

            $this->app->topicMembershipRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Invitation removed.', 'success');
        } catch(AException $e) {
            $this->app->topicMembershipRepository->rollback();

            $this->flashMessage('Could not remove invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listInvites', 'topicId' => $topicId]);
    }

    public function handleManagePrivacy() {
        $topicId = $this->httpGet('topicId', true);

        try {
            $topic = $this->app->topicManager->getTopicById($topicId, $this->getUserId());
        } catch(AException $e) {
            $this->flashMessage('Could not retrieve information about this topic. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        }

        $this->saveToPresenterCache('topic_title', $topic->getTitle());

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);

        $fb = new FormBuilder();

        $fb ->setAction($this->createURL('managePrivacyForm', ['topicId' => $topicId]))
            ->setMethod()
            ->addCheckbox('private', 'Is private?', $topic->isPrivate())
            ->addCheckbox('visible', 'Is visible for non-followers?', $topic->isVisible())
            ->addSubmit('Save')
        ;

        $this->saveToPresenterCache('form', $fb);
    }

    public function renderManagePrivacy() {
        $topicTitle = $this->loadFromPresenterCache('topic_title');
        $links = $this->loadFromPresenterCache('links');
        $form = $this->loadFromPresenterCache('form');

        $this->template->topic_title = $topicTitle;
        $this->template->links = $links;
        $this->template->form = $form;
    }

    public function handleManagePrivacyForm(?FormResponse $fr = null) {
        $topicId = $this->httpGet('topicId');

        if($fr === null) {
            $this->flashMessage('Error processing submitted form.', 'error');
            $this->redirect($this->createURL('managePrivacy', ['topicId' => $topicId]));
        }

        $private = false;

        if(isset($fr->private)) {
            $private = true;
        }

        $visible = false;

        if(isset($fr->visible)) {
            $visible = true;
        }

        try {
            $this->app->topicRepository->beginTransaction();

            $this->app->topicManager->updateTopicPrivacy($this->getUserId(), $topicId, $private, $visible);

            $this->app->topicRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Settings updated successfully.', 'success');
        } catch(Exception $e) {
            $this->app->topicRepository->rollback();
            
            $this->flashMessage('Could not update settings. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('managePrivacy', ['topicId' => $topicId]));
    }

    public function handleFollowersList() {
        $topicId = $this->httpGet('topicId', true);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderFollowersList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentFollowersGrid(HttpRequest $request) {
        $topicId = $request->query['topicId'];

        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->topicMembershipManager->composeQueryForTopicMembers($topicId), 'membershipId');

        $grid->addQueryDependency('topicId', $topicId);

        $grid->addColumnUser('userId', 'User');
        $col = $grid->addColumnText('role', 'Role');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $el = HTML::el('span')
                    ->text(TopicMemberRole::toString($value))
                    ->style('color', TopicMemberRole::getColorByKey($value));

            return $el;
        };
        $grid->addColumnDatetime('dateCreated', 'Member since');
        $col = $grid->addColumnText('daysMember', 'Days of membership');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $diff = time() - strtotime($row->dateCreated);

            return DateTimeFormatHelper::formatSecondsToUserFriendly($diff, 'dHi');
        };

        $grid->enableExport();

        return $grid;
    }

    public function handleBannedWordsList() {
        $topicId = $this->httpGet('topicId', true);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link'),
            LinkBuilder::createSimpleLink('New word', $this->createURL('bannedWordForm', ['topicId' => $topicId]), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderBannedWordsList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    protected function createComponentBannedWordsGrid(HttpRequest $request) {
        $topicId = $request->query['topicId'];

        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->topicContentRegulationRepository->composeQueryForBannedWordsForTopicId($topicId), 'wordId');

        $grid->addQueryDependency('topicId', $topicId);

        $grid->addColumnUser('authorId', 'Author');
        $grid->addColumnText('word', 'Text');
        $grid->addColumnDatetime('dateCreated', 'Date created');

        $edit = $grid->addAction('edit');
        $edit->setTitle('Edit');
        $edit->onCanRender[] = function() {
            return true;
        };
        $edit->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row) use ($topicId) {
            $el = HTML::el('a')
                    ->class('grid-link')
                    ->text('Edit')
                    ->href($this->createURLString('bannedWordForm', ['topicId' => $topicId, 'wordId' => $primaryKey]));

            return $el;
        };

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function() {
            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row) use ($topicId) {
            $el = HTML::el('a')
                    ->class('grid-link')
                    ->text('Delete')
                    ->href($this->createURLString('deleteBannedWord', ['topicId' => $topicId, 'wordId' => $primaryKey]));

            return $el;
        };

        return $grid;
    }

    public function handleBannedWordForm(?FormResponse $fr = null) {
        $topicId = $this->httpGet('topicId', true);

        if($this->httpGet('isFormSubmit') == '1') {
            $word = $fr->word;

            try {
                $this->app->topicContentRegulationRepository->beginTransaction();

                if($this->httpGet('wordId') !== null) {
                    // update
                    $wordId = $this->httpGet('wordId');

                    $this->app->topicContentRegulationRepository->updateBannedWord($wordId, ['word' => $word]);
                } else {
                    // create
                    $wordId = $this->app->topicContentRegulationRepository->createEntityId(EntityManager::TOPIC_BANNED_WORDS);

                    $this->app->topicContentRegulationRepository->createNewBannedWord($wordId, $topicId, $this->getUserId(), $word);
                }

                $this->app->topicContentRegulationRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Banned new word.', 'success');
            } catch(AException $e) {
                $this->app->topicContentRegulationRepository->rollback();

                $this->flashMessage('Could not create new banned word. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect($this->createURL('bannedWordsList', ['topicId' => $topicId]));
        } else {
            $params = [
                'topicId' => $topicId
            ];

            $submitText = 'Create';
            $word = null;

            if($this->httpGet('wordId') !== null) {
                $wordId = $this->httpGet('wordId');
                $bannedWord = $this->app->topicContentRegulationRepository->getBannedWordById($wordId);

                $word = $bannedWord->getText();

                $params['wordId'] = $wordId;

                $submitText = 'Save';
            }

            $form = new FormBuilder();

            $form->setMethod()
                ->setAction($this->createURL('bannedWordForm', $params))
                ->addTextInput('word', 'Word:', $word, true)
                ->addSubmit($submitText)
            ;

            $this->saveToPresenterCache('form', $form);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('bannedWordsList', ['topicId' => $topicId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
        }
    }

    public function renderBannedWordForm() {
        $this->template->form = $this->loadFromPresenterCache('form');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function handleDeleteBannedWord() {
        $topicId = $this->httpGet('topicId', true);
        $wordId = $this->httpGet('wordId', true);

        try {
            $this->app->topicContentRegulationRepository->beginTransaction();

            $this->app->topicContentRegulationRepository->deleteBannedWord($wordId);

            $this->app->topicContentRegulationRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Banned word deleted.', 'success');
        } catch(AException $e) {
            $this->app->topicContentRegulationRepository->rollback();

            $this->flashMessage('Could not delete banned word. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('bannedWordsList', ['topicId' => $topicId]));
    }
}

?>