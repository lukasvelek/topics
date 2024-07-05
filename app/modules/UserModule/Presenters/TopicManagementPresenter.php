<?php

namespace App\Modules\UserModule;

use App\Constants\TopicMemberRole;
use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicMemberEntity;
use App\Entities\TopicPollEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class TopicManagementPresenter extends APresenter {
    public function __construct() {
        parent::__construct('TopicManagementPresenter', 'Topic management');
    }

    public function handleManageRoles() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $topic = $app->topicRepository->getTopicById($topicId);

        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'UserModule:TopicManagement', 'action' => 'userRolesGrid'])
            ->setMethod('GET')
            ->setHeader(['gridPage' => '_page', 'topicId' => '_topicId'])
            ->setFunctionName('getUserRolesGrid')
            ->setFunctionArguments(['_page', '_topicId'])
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-paginator', 'paginator')
        ;

        $this->addScript($arb->build());
        $this->addScript('getUserRolesGrid(0, ' . $topicId . ')');

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
        $gridSize = $app->cfg['GRID_SIZE'];

        $members = $app->topicMembershipManager->getTopicMembers($topicId, $gridSize, ($page * $gridSize));
        $allMembersCount = count($app->topicMembershipManager->getTopicMembers($topicId, 0, 0, false));
        $lastPage = ceil($allMembersCount / $gridSize) - 1;

        $gb = new GridBuilder();

        $gb->addDataSource($members);
        $gb->addColumns(['userId' => 'User', 'role' => 'Role']);
        $gb->addOnColumnRender('userId', function(TopicMemberEntity $tme) use ($app) {
            $user = $app->userRepository->getUserById($tme->getUserId());

            return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserAdmin:Users', 'action' => 'profile', 'userId' => $tme->getUserId()], 'post-data-link');
        });
        $gb->addOnColumnRender('role', function (TopicMemberEntity $tme) {
            $text = TopicMemberRole::toString($tme->getRole());

            $span = HTML::span();
            $span->setText($text);
            $span->setColor(TopicMemberRole::getColorByKey($tme->getRole()));
            
            return $span->render();
        });
        $gb->addAction(function(TopicMemberEntity $tme) use ($app) {
            $link = LinkBuilder::createSimpleLink('Change role', ['page' => 'UserModule:TopicManagement', 'action' => 'changeRoleForm', 'topicId' => $tme->getTopicId(), 'userId' => $tme->getUserId()], 'post-data-link');

            if($app->actionAuthorizator->canChangeUserTopicRole($tme->getTopicId(), $app->currentUser->getId(), $tme->getUserId())) {
                return $link;
            } else {
                return '-';
            }
        });

        $paginator = $gb->createGridControls2('getUserRolesGrid', $page, $lastPage, ['topicId' => $topicId]);

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator]);
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

            $ok = false;

            $app->topicMembershipRepository->beginTransaction();

            try {
                $app->topicMembershipManager->changeRole($topicId, $userId, $app->currentUser->getId(), $role);

                $ok = true;
            } catch(AException $e) {
                $app->topicMembershipRepository->rollback();
                $this->flashMessage('Could not change role of the selected user. Reason: ' . $e->getMessage(), 'error');
            }

            if($ok) {
                $app->topicMembershipRepository->commit();
                $this->flashMessage('Role of the selected user changed.', 'success');
            }

            $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'manageRoles', 'topicId' => $topicId]);
        } else {
            $topic = $app->topicRepository->getTopicById($topicId);

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
    }

    public function renderChangeRoleForm() {
        $form = $this->loadFromPresenterCache('form');
        $topic = $this->loadFromPresenterCache('topic');
        
        $this->template->form = $form;
        $this->template->topic_title = $topic->getTitle();
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
            ->updateHTMLElement('grid-paginator', 'paginator')
        ;

        $this->addScript($arb->build());
        $this->addScript('getPollGrid(0, ' . $topicId . ');');

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

        $gridSize = $app->cfg['GRID_SIZE'];

        $polls = $app->topicPollRepository->getPollsForTopicForGrid($topicId, $gridSize, ($gridSize * $page));
        $pollCount = count($app->topicPollRepository->getPollsForTopicForGrid($topicId, 0, 0));
        $lastPage = ceil($pollCount / $gridSize) - 1;

        $gb = new GridBuilder();
        $gb->addColumns(['author' => 'Author', 'title' => 'Title', 'status' => 'Status', 'dateCreated' => 'Date created', 'dateValid' => 'Valid until', 'votes' => 'Votes']);
        $gb->addDataSource($polls);
        $gb->addOnColumnRender('author', function(TopicPollEntity $tpe) use ($app) {
            $user = $app->userRepository->getUserById($tpe->getAuthorId());

            return UserEntity::createUserProfileLink($user);
        });
        $gb->addOnColumnRender('dateCreated', function(TopicPollEntity $tpe) {
            return DateTimeFormatHelper::formatDateToUserFriendly($tpe->getDateCreated());
        });
        $gb->addOnColumnRender('status', function(TopicPollEntity $tpe) {
            $code = function (string $text, bool $response) {
                return '<span style="color: ' . ($response ? 'green' : 'red') . '">' . $text . '</span>';
            };

            if($tpe->getDateValid() === null) {
                return $code('Active', true);
            }
            if(strtotime($tpe->getDateValid()) > time()) {
                return $code('Active', true);
            }

            return $code('Inactive', false);
        });
        $gb->addOnColumnRender('dateValid', function(TopicPollEntity $tpe) {
            if($tpe->getDateValid() === null) {
                return '-';
            }

            return DateTimeFormatHelper::formatDateToUserFriendly($tpe->getDateValid());
        });
        $gb->addOnColumnRender('votes', function(TopicPollEntity $tpe) use ($app) {
            $votes = $app->topicPollRepository->getPollResponses($tpe->getId());

            return count($votes);
        });
        $gb->addAction(function(TopicPollEntity $tpe) {
            return LinkBuilder::createSimpleLink('Analytics', ['page' => 'UserModule:Topics', 'action' => 'pollAnalytics', 'pollId' => $tpe->getId(), 'backPage' => 'UserModule:TopicManagement', 'backAction' => 'listPolls', 'topicId' => $tpe->getTopicId()], 'post-data-link');
        });
        $gb->addAction(function(TopicPollEntity $tpe) {
            if($tpe->getDateValid() === null || strtotime($tpe->getDateValid()) > time()) {
                return LinkBuilder::createSimpleLink('Deactivate', ['page' => 'UserModule:TopicManagement', 'action' => 'deactivatePoll', 'pollId' => $tpe->getId(), 'topicId' => $tpe->getTopicId()], 'post-data-link');
            } else {
                return LinkBuilder::createSimpleLink('Reactivate for 24 hrs', ['page' => 'UserModule:TopicManagement', 'action' => 'reactivatePoll', 'pollId' => $tpe->getId(), 'topicId' => $tpe->getTopicId()], 'post-data-link');
            }
        });

        $paginator = $gb->createGridControls2('getPollGrid', $page, $lastPage, [$topicId]);

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator]);
    }

    public function handleDeactivatePoll() {
        global $app;

        $pollId = $this->httpGet('pollId');
        $topicId = $this->httpGet('topicId');

        $app->topicPollRepository->closePoll($pollId);

        $this->flashMessage('Poll deactivated.', 'success');
        $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listPolls', 'topicId' => $topicId]);
    }

    public function handleReactivatePoll() {
        global $app;

        $pollId = $this->httpGet('pollId');
        $topicId = $this->httpGet('topicId');

        $tomorrow = new DateTime();
        $tomorrow->modify('+1d');
        $tomorrow = $tomorrow->getResult();

        $app->topicPollRepository->openPoll($pollId, $tomorrow);

        $this->flashMessage('Poll reactivated.', 'success');
        $this->redirect(['page' => 'UserModule:TopicManagement', 'action' => 'listPolls', 'topicId' => $topicId]);
    }
}

?>