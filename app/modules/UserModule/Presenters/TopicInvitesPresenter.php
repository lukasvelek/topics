<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicInviteEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class TopicInvitesPresenter extends AUserPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('TopicInvitesPresenter', 'Topic invites');
    }
    
    public function startup() {
        parent::startup();
        
        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setAction($this, 'getInvitesGrid')
            ->setMethod()
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionArguments(['_page'])
            ->setFunctionName('getInvitesGrid')
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getInvitesGrid(-1)');
    }

    public function renderList() {}

    public function actionGetInvitesGrid() {
        $gridPage = $this->httpGet('gridPage');
        $gridSize = $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_TOPIC_INVITES, $gridPage);

        $validOnly = true;

        $invites = $this->app->topicInviteRepository->getInvitesForUserForGrid($this->getUserId(), $gridSize, ($gridSize * $page), $validOnly);
        $totalInviteCount = count($this->app->topicInviteRepository->getInvitesForUserForGrid($this->getUserId(), 0, 0, $validOnly));

        $lastPage = ceil($totalInviteCount / $gridSize);

        $topicIds = $this->app->topicInviteRepository->getAllTopicsInUserInvites($this->getUserId(), $validOnly);
        $topics = $this->app->topicRepository->bulkGetTopicsByIds($topicIds, true);

        $gb = new GridBuilder();

        $gb->addDataSource($invites);
        $gb->addColumns(['topic' => 'Topic', 'dateValid' => 'Valid until']);
        $gb->addOnColumnRender('topic', function(Cell $cell, TopicInviteEntity $tie) use ($topics) {
            if(array_key_exists($tie->getTopicId(), $topics)) {
                $topic = $topics[$tie->getTopicId()];

                return LinkBuilder::createSimpleLink($topic->getTitle(), ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topic->getId()], 'grid-link');
            } else {
                return '-';
            }
        });
        $gb->addOnColumnRender('dateValid', function(Cell $cell, TopicInviteEntity $tie) {
            return DateTimeFormatHelper::formatDateToUserFriendly($tie->getDateValid());
        });
        $gb->addAction(function(TopicInviteEntity $tie) {
            $now = new DateTime();
            $now = $now->getResult();

            if(strtotime($tie->getDateValid()) > strtotime($now)) {
                return LinkBuilder::createSimpleLink('Accept', $this->createURL('acceptInvite', ['topicId' => $tie->getTopicId()]), 'grid-link');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(TopicInviteEntity $tie) {
            $now = new DateTime();
            $now = $now->getResult();

            if(strtotime($tie->getDateValid()) > strtotime($now)) {
                return LinkBuilder::createSimpleLink('Reject', $this->createURL('rejectInvite', ['topicId' => $tie->getTopicId()]), 'grid-link');
            } else {
                return '-';
            }
        });
        $gb->addAction(function(TopicInviteEntity $tie) {
            $now = new DateTime();
            $now = $now->getResult();

            if(strtotime($tie->getDateValid()) <= strtotime($now)) {
                return LinkBuilder::createSimpleLink('Delete', $this->createURL('deleteInvite', ['topicId' => $tie->getTopicId()]), 'grid-link');
            } else {
                return '-';
            }
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $totalInviteCount, 'getInvitesGrid');

        return ['grid' => $gb->build()];
    }

    public function handleAcceptInvite() {
        $topicId = $this->httpGet('topicId');

        try {
            $this->app->topicRepository->beginTransaction();

            $this->app->topicMembershipManager->acceptInvite($topicId, $this->getUserId());

            $this->app->topicRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Invite accepted.', 'success');
        } catch(AException $e) {
            $this->app->topicRepository->rollback();

            $this->flashMessage('Could not accept invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleRejectInvite() {
        $topicId = $this->httpGet('topicId');

        try {
            $this->app->topicRepository->beginTransaction();

            $this->app->topicMembershipManager->rejectInvite($topicId, $this->getUserId());

            $this->app->topicRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Invite rejected.', 'success');
        } catch(AException $e) {
            $this->app->topicRepository->rollback();

            $this->flashMessage('Could not reject invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleDeleteInvite() {
        $topicId = $this->httpGet('topicId');

        try {
            $this->app->topicRepository->beginTransaction();

            $this->app->topicMembershipManager->removeInvite($topicId, $this->getUserId());

            $this->app->topicRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Invite deleted.', 'success');
        } catch(AException $e) {
            $this->app->topicRepository->rollback();
            
            $this->flashMessage('Could not delete invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createUrl('list'));
    }
}

?>