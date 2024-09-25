<?php

namespace App\Modules\UserModule;

use App\Constants\TopicMemberRole;
use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicBannedWordEntity;
use App\Entities\TopicInviteEntity;
use App\Entities\TopicMemberEntity;
use App\Entities\TopicPollEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;
use Exception;

class TopicManagementPresenter extends AUserPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('TopicManagementPresenter', 'Topic management');
    }
    
    public function startup() {
        parent::startup();
        
        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }

    public function handleManageRoles() {
        $topicId = $this->httpGet('topicId');

        try {
            $topic = $this->app->topicManager->getTopicById($topicId, $this->getUserId());
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
        }

        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'UserModule:TopicManagement', 'action' => 'userRolesGrid'])
            ->setMethod('GET')
            ->setHeader(['gridPage' => '_page', 'topicId' => '_topicId'])
            ->setFunctionName('getUserRolesGrid')
            ->setFunctionArguments(['_page', '_topicId'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getUserRolesGrid(-1, \'' . $topicId . '\')');

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

    public function actionUserRolesGrid() {
        $topicId = $this->httpGet('topicId');
        $gridPage = $this->httpGet('gridPage');
        $gridSize = $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_USER_TOPIC_ROLES, $gridPage, [$topicId]);

        $members = $this->app->topicMembershipManager->getTopicMembers($topicId, $gridSize, ($page * $gridSize));
        $allMembersCount = count($this->app->topicMembershipManager->getTopicMembers($topicId, 0, 0, false));
        $lastPage = ceil($allMembersCount / $gridSize);

        $gb = new GridBuilder();

        $gb->addDataSource($members);
        $gb->addColumns(['userId' => 'User', 'role' => 'Role']);
        $gb->addOnColumnRender('userId', function(Cell $cell, TopicMemberEntity $tme) {
            $user = $this->app->userRepository->getUserById($tme->getUserId());

            return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserAdmin:Users', 'action' => 'profile', 'userId' => $tme->getUserId()], 'grid-link');
        });
        $gb->addOnColumnRender('role', function (Cell $cell, TopicMemberEntity $tme) {
            $text = TopicMemberRole::toString($tme->getRole());

            $cell->setTextColor(TopicMemberRole::getColorByKey($tme->getRole()));
            $cell->setValue($text);

            return $cell;
        });
        $gb->addAction(function(TopicMemberEntity $tme) {
            $link = LinkBuilder::createSimpleLink('Change role', ['page' => 'UserModule:TopicManagement', 'action' => 'changeRoleForm', 'topicId' => $tme->getTopicId(), 'userId' => $tme->getUserId()], 'grid-link');

            if($this->app->actionAuthorizator->canChangeUserTopicRole($tme->getTopicId(), $this->getUserId(), $tme->getUserId())) {
                return $link;
            } else {
                return '-';
            }
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $allMembersCount, 'getUserRolesGrid', [$topicId]);

        return ['grid' => $gb->build()];
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

        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'UserModule:TopicManagement', 'action' => 'getPollGrid'])
            ->setMethod()
            ->setHeader(['gridPage' => '_page', 'topicId' => '_topicId'])
            ->setFunctionName('getPollGrid')
            ->setFunctionArguments(['_page', '_topicId'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getPollGrid(-1, \'' . $topicId . '\');');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderListPolls() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function actionGetPollGrid() {
        $topicId = $this->httpGet('topicId');
        $gridPage = $this->httpGet('gridPage');

        $gridSize = $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_TOPIC_POLLS, $gridPage, [$topicId]);

        if($this->app->actionAuthorizator->canSeeAllTopicPolls($this->getUserId(), $topicId)) {
            $polls = $this->app->topicPollRepository->getPollsForTopicForGrid($topicId, $gridSize, ($gridSize * $page));
            $pollCount = count($this->app->topicPollRepository->getPollsForTopicForGrid($topicId, 0, 0));
            $lastPage = ceil($pollCount / $gridSize);
        } else {
            $polls = $this->app->topicPollRepository->getMyPollsForTopicForGrid($topicId, $this->getUserId(), $gridSize, ($gridSize * $page));
            $pollCount = count($this->app->topicPollRepository->getMyPollsForTopicForGrid($topicId, $this->getUserId(), 0, 0));
            $lastPage = ceil($pollCount / $gridSize);
        }

        $gb = new GridBuilder();
        $gb->addColumns(['author' => 'Author', 'title' => 'Title', 'status' => 'Status', 'dateCreated' => 'Date created', 'dateValid' => 'Valid until', 'votes' => 'Votes']);
        $gb->addDataSource($polls);
        $gb->addOnColumnRender('author', function(Cell $cell, TopicPollEntity $tpe) {
            $user = $this->app->userRepository->getUserById($tpe->getAuthorId());

            return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getID()], 'grid-link');
        });
        $gb->addOnColumnRender('dateCreated', function(Cell $cell, TopicPollEntity $tpe) {
            return DateTimeFormatHelper::formatDateToUserFriendly($tpe->getDateCreated());
        });
        $gb->addOnColumnRender('status', function(Cell $cell, TopicPollEntity $tpe) {
            if($tpe->getDateValid() === null || strtotime($tpe->getDateValid()) > time()) {
                $cell->setTextColor('green');
                $cell->setValue('Active');
            } else {
                $cell->setTextColor('red');
                $cell->setValue('Inactive');
            }

            return $cell;
        });
        $gb->addOnColumnRender('dateValid', function(Cell $cell, TopicPollEntity $tpe) {
            if($tpe->getDateValid() === null) {
                return '-';
            }

            return DateTimeFormatHelper::formatDateToUserFriendly($tpe->getDateValid());
        });
        $gb->addOnColumnRender('votes', function(Cell $cell, TopicPollEntity $tpe) {
            $votes = $this->app->topicPollRepository->getPollResponses($tpe->getId());

            return count($votes);
        });
        $gb->addAction(function(TopicPollEntity $tpe) {
            if($this->app->actionAuthorizator->canSeePollAnalytics($this->getUserId(), $tpe->getTopicId(), $tpe)) {
                return LinkBuilder::createSimpleLink('Analytics', ['page' => 'UserModule:Topics', 'action' => 'pollAnalytics', 'pollId' => $tpe->getId(), 'backPage' => 'UserModule:TopicManagement', 'backAction' => 'listPolls', 'topicId' => $tpe->getTopicId()], 'grid-link');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(TopicPollEntity $tpe) {
            if($this->app->actionAuthorizator->canDeactivePoll($this->getUserId(), $tpe->getTopicId(), $tpe)) {
                if($tpe->getDateValid() === null || strtotime($tpe->getDateValid()) > time()) {
                    return LinkBuilder::createSimpleLink('Deactivate', ['page' => 'UserModule:TopicManagement', 'action' => 'deactivatePoll', 'pollId' => $tpe->getId(), 'topicId' => $tpe->getTopicId()], 'grid-link');
                } else {
                    return LinkBuilder::createSimpleLink('Reactivate for 24 hrs', ['page' => 'UserModule:TopicManagement', 'action' => 'reactivatePoll', 'pollId' => $tpe->getId(), 'topicId' => $tpe->getTopicId()], 'grid-link');
                }
            } else {
                return '-';
            }
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $pollCount, 'getPollGrid', [$topicId]);

        return ['grid' => $gb->build()];
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

        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'UserModule:TopicManagement', 'action' => 'getInvitesGrid'])
            ->setMethod()
            ->setHeader(['gridPage' => '_page', 'topicId' => '_topicId'])
            ->setFunctionName('getInvitesGrid')
            ->setFunctionArguments(['_page', '_topicId'])
            ->updateHTMLElement('grid-content', 'grid');

        $this->addScript($arb->build());
        $this->addScript('getInvitesGrid(-1, ' . $topicId . ')');

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

    public function actionGetInvitesGrid() {
        $topicId = $this->httpGet('topicId');
        $gridPage = $this->httpGet('gridPage');
        
        $gridSize = $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_TOPIC_INVITES_ALL, $gridPage, [$topicId]);

        $invites = $this->app->topicInviteRepository->getInvitesForGrid($topicId, true, $gridSize, ($page * $gridSize));
        $inviteCount = count($this->app->topicInviteRepository->getInvitesForGrid($topicId, true, 0, 0));

        $lastPage = ceil($inviteCount / $gridSize);

        $userIds = [];
        foreach($invites as $invite) {
            if(!in_array($invite->getUserId(), $userIds)) {
                $userIds[] = $invite->getUserId();
            }
        }

        $users = $this->app->userRepository->getUsersByIdBulk($userIds, true);

        $gb = new GridBuilder();
        $gb->addDataSource($invites);
        $gb->addColumns(['user' => 'User', 'dateValid' => 'Valid until']);
        $gb->addOnColumnRender('user', function(Cell $cell, TopicInviteEntity $invite) use ($users) {
            if(array_key_exists($invite->getUserId(), $users)) {
                return $users[$invite->getUserId()]->getUsername();
            } else {
                return '-';
            }
        });
        $gb->addOnColumnRender('dateValid', function(Cell $cell, TopicInviteEntity $invite) {
            return DateTimeFormatHelper::formatDateToUserFriendly($invite->getDateValid());
        });
        $gb->addAction(function(TopicInviteEntity $invite) {
            return LinkBuilder::createSimpleLink('Remove invite', ['page' => 'UserModule:TopicManagement', 'action' => 'removeInvite', 'topicId' => $invite->getTopicId(), 'userId' => $invite->getUserId()], 'grid-link');
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $inviteCount, 'getInvitesGrid', [$topicId]);

        return ['grid' => $gb->build()];
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

        $checkInvite = function(int $userId) use ($invites) {
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

        $checkMembership = function(int $userId) use ($members) {
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

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setAction($this, 'getFollowersGrid')
            ->setHeader(['topicId' => '_topicId', 'gridPage' => '_page'])
            ->setFunctionName('getFollowersGrid')
            ->setFunctionArguments(['_page', '_topicId'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb);
        $this->addScript('getFollowersGrid(0, \'' . $topicId . '\')');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderFollowersList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetFollowersGrid() {
        $topicId = $this->httpGet('topicId');
        $gridPage = $this->httpGet('gridPage');

        $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_TOPIC_FOLLOWERS, $gridPage, [$topicId]);

        $offset = $page * $gridSize;

        $members = $this->app->topicMembershipManager->getTopicMembers($topicId, $gridSize, $offset, false);
        $totalCount = $this->app->topicMembershipManager->getTopicMemberCount($topicId);

        $lastPage = ceil($totalCount / $gridSize);

        $grid = new GridBuilder();
        $grid->addColumns(['userId' => 'User', 'role' => 'Role', 'dateCreated' => 'Member from', 'daysMember' => 'Days of membership']);
        $grid->addDataSource($members);
        $grid->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getFollowersGrid', [$topicId]);
        $grid->addOnColumnRender('role', function(Cell $cell, TopicMemberEntity $tme) {
            $text = TopicMemberRole::toString($tme->getRole());
            $color = TopicMemberRole::getColorByKey($tme->getRole());
            
            $cell->setTextColor($color);
            $cell->setValue($text);
            
            return $cell;
        });
        $grid->addOnColumnRender('daysMember', function(Cell $cell, TopicMemberEntity $tme) {
            $dateFrom = $tme->getDateCreated();
            
            $diff = time() - strtotime($dateFrom);
            
            return DateTimeFormatHelper::formatSecondsToUserFriendly($diff);
        });
        $grid->addOnExportRender('role', function(TopicMemberEntity $tme) {
            return TopicMemberRole::toString($tme->getRole());
        });
        $grid->addOnExportRender('daysMember', function(TopicMemberEntity $tme) {
            $dateFrom = $tme->getDateCreated();
            
            $diff = time() - strtotime($dateFrom);
            
            return DateTimeFormatHelper::formatSecondsToUserFriendly($diff);
        });

        $this->getGridReducer()->applyReducer($grid);
        
        $grid->addGridExport(function() use ($topicId) {
            return $this->app->topicMembershipManager->getTopicMembers($topicId, 0, 0, false);
        }, GridHelper::GRID_TOPIC_FOLLOWERS, $this->logger);
        

        return ['grid' => $grid->build()];
    }

    public function handleBannedWordsList() {
        $topicId = $this->httpGet('topicId', true);

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setAction($this, 'getBannedWordsGrid')
            ->setFunctionName('getBannedWordsGrid')
            ->setFunctionArguments(['_page', '_topicId'])
            ->setHeader(['gridPage' => '_page', 'topicId' => '_topicId'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb);
        $this->addScript('getBannedWordsGrid(0, \'' . $topicId . '\')');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link'),
            LinkBuilder::createSimpleLink('New word', $this->createURL('bannedWordForm', ['topicId' => $topicId]), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));
    }

    public function renderBannedWordsList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetBannedWordsGrid() {
        

        $topicId = $this->httpGet('topicId');
        $gridPage = $this->httpGet('gridPage');

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_TOPIC_BANNED_WORDS, $gridPage, ['topicId' => $topicId]);

        $gridSize = $this->app->getGridSize();

        $bannedWords = $this->app->topicContentRegulationRepository->getBannedWordsForTopicForGrid($topicId, $gridSize, ($page * $gridSize));
        $bannedWordsTotalCount = count($this->app->topicContentRegulationRepository->getBannedWordsForTopicForGrid($topicId, 0, 0));

        $lastPage = ceil($bannedWordsTotalCount / $gridSize);

        $grid = new GridBuilder();
        $grid->addColumns(['authorId' => 'Author', 'text' => 'Text', 'dateCreated' => 'Date created']);
        $grid->addDataSource($bannedWords);

        $grid->addOnColumnRender('authorId', function(Cell $cell, TopicBannedWordEntity $tbwe) {
            $user = $this->app->userRepository->getUserById($tbwe->getAuthorId());

            if($user !== null) {
                return UserEntity::createUserProfileLink($user);
            } else {
                return '-';
            }
        });

        $grid->addAction(function(TopicBannedWordEntity $tbwe) {
            return LinkBuilder::createSimpleLink('Edit', $this->createURL('bannedWordForm', ['topicId' => $tbwe->getTopicId(), 'wordId' => $tbwe->getId()]), 'grid-link');
        });
        $grid->addAction(function(TopicBannedWordEntity $tbwe) {
            return LinkBuilder::createSimpleLink('Delete', $this->createURL('deleteBannedWord', ['topicId' => $tbwe->getTopicId(), 'wordId' => $tbwe->getId()]), 'grid-link');
        });

        $grid->addGridPaging($page, $lastPage, $gridSize, $bannedWordsTotalCount, 'getBannedWordsGrid', [$topicId]);

        $gr = $this->getGridReducer();
        $gr->applyReducer($grid);

        return ['grid' => $grid->build()];
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