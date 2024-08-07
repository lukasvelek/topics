<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicEntity;
use App\Entities\TopicInviteEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class TopicInvitesPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicInvitesPresenter', 'Topic invites');
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
        $this->addScript('getInvitesGrid(0)');
    }

    public function renderList() {}

    public function actionGetInvitesGrid() {
        global $app;

        $page = $this->httpGet('gridPage');

        $gridSize = $gridSize = $app->getGridSize();

        $validOnly = true;

        $invites = $app->topicInviteRepository->getInvitesForUserForGrid($app->currentUser->getId(), $gridSize, ($gridSize * $page), $validOnly);
        $totalInviteCount = count($app->topicInviteRepository->getInvitesForUserForGrid($app->currentUser->getId(), 0, 0, $validOnly));

        $lastPage = ceil($totalInviteCount / $gridSize);

        $topicIds = $app->topicInviteRepository->getAllTopicsInUserInvites($app->currentUser->getId(), $validOnly);
        $topics = $app->topicRepository->bulkGetTopicsByIds($topicIds, true);

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

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleAcceptInvite() {
        global $app;

        $topicId = $this->httpGet('topicId');

        try {
            $app->topicMembershipManager->acceptInvite($topicId, $app->currentUser->getId());

            $this->flashMessage('Invite accepted.', 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not accept invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleRejectInvite() {
        global $app;

        $topicId = $this->httpGet('topicId');

        try {
            $app->topicMembershipManager->rejectInvite($topicId, $app->currentUser->getId());

            $this->flashMessage('Invite rejected.', 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not reject invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }

    public function handleDeleteInvite() {
        global $app;

        $topicId = $this->httpGet('topicId');

        try {
            $app->topicMembershipManager->removeInvite($topicId, $app->currentUser->getId());

            $this->flashMessage('Invite deleted.', 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not delete invite. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createUrl('list'));
    }
}

?>