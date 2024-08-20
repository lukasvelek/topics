<?php

namespace App\Modules\UserModule;

use App\Constants\TopicMemberRole;
use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicInviteEntity;
use App\Entities\TopicMemberEntity;
use App\Entities\TopicPollEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;
use Exception;

class TopicManagementPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicManagementPresenter', 'Topic management');
    }

    public function handleManageRoles() {
        global $app;

        $topicId = $this->httpGet('topicId');

        try {
            $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
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
        $this->addScript('getUserRolesGrid(0, \'' . $topicId . '\')');

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
        global $app;

        $topicId = $this->httpGet('topicId');
        $page = $this->httpGet('gridPage');
        $gridSize = $gridSize = $app->getGridSize();

        $members = $app->topicMembershipManager->getTopicMembers($topicId, $gridSize, ($page * $gridSize));
        $allMembersCount = count($app->topicMembershipManager->getTopicMembers($topicId, 0, 0, false));
        $lastPage = ceil($allMembersCount / $gridSize);

        $gb = new GridBuilder();

        $gb->addDataSource($members);
        $gb->addColumns(['userId' => 'User', 'role' => 'Role']);
        $gb->addOnColumnRender('userId', function(Cell $cell, TopicMemberEntity $tme) use ($app) {
            $user = $app->userRepository->getUserById($tme->getUserId());

            return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserAdmin:Users', 'action' => 'profile', 'userId' => $tme->getUserId()], 'grid-link');
        });
        $gb->addOnColumnRender('role', function (Cell $cell, TopicMemberEntity $tme) {
            $text = TopicMemberRole::toString($tme->getRole());

            $cell->setTextColor(TopicMemberRole::getColorByKey($tme->getRole()));
            $cell->setValue($text);

            return $cell;
        });
        $gb->addAction(function(TopicMemberEntity $tme) use ($app) {
            $link = LinkBuilder::createSimpleLink('Change role', ['page' => 'UserModule:TopicManagement', 'action' => 'changeRoleForm', 'topicId' => $tme->getTopicId(), 'userId' => $tme->getUserId()], 'grid-link');

            if($app->actionAuthorizator->canChangeUserTopicRole($tme->getTopicId(), $app->currentUser->getId(), $tme->getUserId())) {
                return $link;
            } else {
                return '-';
            }
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $allMembersCount, 'getUserRolesGrid', [$topicId]);

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleChangeRoleForm() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $userId = $this->httpGet('userId');
        
        if(!$app->actionAuthorizator->canChangeUserTopicRole($topicId, $app->currentUser->getId(), $userId)) {
            $this->flashMessage('You are not authorized to change role of the selected user in this topic.', 'error');
            $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
        }

        if($this->httpGet('isSubmit') == '1') {
            $role = $this->httpPost('role');

            $oldRole = $app->topicMembershipManager->getFollowRole($topicId, $userId);
            $oldRole = '<span style="color: ' . TopicMemberRole::getColorByKey($oldRole) . '">' . TopicMemberRole::toString($oldRole) . '</span>';

            $newRole = '<span style="color: ' . TopicMemberRole::getColorByKey($role) . '">' . TopicMemberRole::toString($role) . '</span>';

            try {
                $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
            } catch(AException $e) {
                $this->flashMessage('Could not change role of the selected user. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
            }

            try {
                $app->topicMembershipRepository->beginTransaction();

                $app->topicMembershipManager->changeRole($topicId, $userId, $app->currentUser->getId(), $role);

                $app->notificationManager->createNewTopicRoleChangedNotification($userId, LinkBuilder::createSimpleLinkObject($topic->getTitle(), ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link'), $oldRole, $newRole);

                $app->topicMembershipRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Role of the selected user changed.', 'success');
            } catch(AException $e) {
                $app->topicMembershipRepository->rollback();
                $this->flashMessage('Could not change role of the selected user. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
        } else {
            try {
                $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
            } catch(AException $e) {
                $this->flashMessage($e->getMessage(), 'error');
                $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
            }

            $this->saveToPresenterCache('topic', $topic);

            $memberRole = $app->topicMembershipManager->getFollowRole($topicId, $userId);

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
        $this->addScript('getPollGrid(0, \'' . $topicId . '\');');

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
        global $app;

        $topicId = $this->httpGet('topicId');
        $page = $this->httpGet('gridPage');

        $gridSize = $gridSize = $app->getGridSize();

        if($app->actionAuthorizator->canSeeAllTopicPolls($app->currentUser->getId(), $topicId)) {
            $polls = $app->topicPollRepository->getPollsForTopicForGrid($topicId, $gridSize, ($gridSize * $page));
            $pollCount = count($app->topicPollRepository->getPollsForTopicForGrid($topicId, 0, 0));
            $lastPage = ceil($pollCount / $gridSize);
        } else {
            $polls = $app->topicPollRepository->getMyPollsForTopicForGrid($topicId, $app->currentUser->getId(), $gridSize, ($gridSize * $page));
            $pollCount = count($app->topicPollRepository->getMyPollsForTopicForGrid($topicId, $app->currentUser->getId(), 0, 0));
            $lastPage = ceil($pollCount / $gridSize);
        }

        $gb = new GridBuilder();
        $gb->addColumns(['author' => 'Author', 'title' => 'Title', 'status' => 'Status', 'dateCreated' => 'Date created', 'dateValid' => 'Valid until', 'votes' => 'Votes']);
        $gb->addDataSource($polls);
        $gb->addOnColumnRender('author', function(Cell $cell, TopicPollEntity $tpe) use ($app) {
            $user = $app->userRepository->getUserById($tpe->getAuthorId());

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
        $gb->addOnColumnRender('votes', function(Cell $cell, TopicPollEntity $tpe) use ($app) {
            $votes = $app->topicPollRepository->getPollResponses($tpe->getId());

            return count($votes);
        });
        $gb->addAction(function(TopicPollEntity $tpe) use ($app) {
            if($app->actionAuthorizator->canSeePollAnalytics($app->currentUser->getId(), $tpe->getTopicId(), $tpe)) {
                return LinkBuilder::createSimpleLink('Analytics', ['page' => 'UserModule:Topics', 'action' => 'pollAnalytics', 'pollId' => $tpe->getId(), 'backPage' => 'UserModule:TopicManagement', 'backAction' => 'listPolls', 'topicId' => $tpe->getTopicId()], 'grid-link');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(TopicPollEntity $tpe) use ($app) {
            if($app->actionAuthorizator->canDeactivePoll($app->currentUser->getId(), $tpe->getTopicId(), $tpe)) {
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

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleDeactivatePoll() {
        global $app;

        $pollId = $this->httpGet('pollId');
        $topicId = $this->httpGet('topicId');

        try {
            $app->topicPollRepository->beginTransaction();

            $app->topicPollRepository->closePoll($pollId);

            $app->topicPollRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Poll deactivated.', 'success');
        } catch(AException $e) {
            $app->topicPollRepository->rollback();

            $this->flashMessage('Poll could not be deactivated. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listPolls', 'topicId' => $topicId]);
    }

    public function handleReactivatePoll() {
        global $app;

        $pollId = $this->httpGet('pollId');
        $topicId = $this->httpGet('topicId');

        $tomorrow = new DateTime();
        $tomorrow->modify('+1d');
        $tomorrow = $tomorrow->getResult();

        try {
            $app->topicPollRepository->beginTransaction();

            $app->topicPollRepository->openPoll($pollId, $tomorrow);

            $app->topicPollRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Poll reactivated.', 'success');
        } catch(AException $e) {
            $app->topicPollRepository->rollback();

            $this->flashMessage('Poll could not be reactivated. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listPolls', 'topicId' => $topicId]);
    }

    public function handleListInvites() {
        global $app;

        $topicId = $this->httpGet('topicId', true);

        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'UserModule:TopicManagement', 'action' => 'getInvitesGrid'])
            ->setMethod()
            ->setHeader(['gridPage' => '_page', 'topicId' => '_topicId'])
            ->setFunctionName('getInvitesGrid')
            ->setFunctionArguments(['_page', '_topicId'])
            ->updateHTMLElement('grid-content', 'grid');

        $this->addScript($arb->build());
        $this->addScript('getInvitesGrid(0, ' . $topicId . ')');

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
        global $app;

        $topicId = $this->httpGet('topicId');
        $page = $this->httpGet('gridPage');
        
        $gridSize = $gridSize = $app->getGridSize();

        $invites = $app->topicInviteRepository->getInvitesForGrid($topicId, true, $gridSize, ($page * $gridSize));
        $inviteCount = count($app->topicInviteRepository->getInvitesForGrid($topicId, true, 0, 0));

        $lastPage = ceil($inviteCount / $gridSize);

        $userIds = [];
        foreach($invites as $invite) {
            if(!in_array($invite->getUserId(), $userIds)) {
                $userIds[] = $invite->getUserId();
            }
        }

        $users = $app->userRepository->getUsersByIdBulk($userIds, true);

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

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleInviteForm() {
        global $app;

        $topicId = $this->httpGet('topicId');

        if($this->httpGet('isFormSubmit') == '1') {
            $userId = $this->httpPost('userSelect');

            try {
                $app->topicMembershipRepository->beginTransaction();

                $app->topicMembershipManager->inviteUser($topicId, $userId, $app->currentUser->getId());

                $app->topicMembershipRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('User invited.', 'success');
            } catch(AException $e) {
                $app->topicMembershipRepository->rollback();

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
        global $app;

        $username = $this->httpGet('query');
        $topicId = $this->httpGet('topicId');

        $users = $app->userRepository->searchUsersByUsername($username);

        $invites = $app->topicMembershipManager->getInvitesForTopic($topicId);

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

        $members = $app->topicMembershipManager->getTopicMembers($topicId, 0, 0, false);

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

        $this->ajaxSendResponse(['users' => $usersOptions, 'empty' => empty($usersOptions)]);
    }

    public function handleRemoveInvite() {
        global $app;

        $userId = $this->httpGet('userId');
        $topicId = $this->httpGet('topicId');

        try {
            $app->topicMembershipRepository->beginTransaction();

            $app->topicMembershipManager->removeInvite($topicId, $userId);

            $app->topicMembershipRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Invitation removed.', 'success');
        } catch(AException $e) {
            $app->topicMembershipRepository->rollback();

            $this->flashMessage('Could not remove invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listInvites', 'topicId' => $topicId]);
    }

    public function handleManagePrivacy() {
        global $app;

        $topicId = $this->httpGet('topicId', true);

        try {
            $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
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
        global $app;

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
            $app->topicRepository->beginTransaction();

            $app->topicManager->updateTopicPrivacy($app->currentUser->getId(), $topicId, $private, $visible);

            $app->topicRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Settings updated successfully.', 'success');
        } catch(Exception $e) {
            $app->topicRepository->rollback();
            
            $this->flashMessage('Could not update settings. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('managePrivacy', ['topicId' => $topicId]));
    }
}

?>