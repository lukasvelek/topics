<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportCategory;
use App\Entities\TopicEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use Exception;

class ManageTopicsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageTopicsPresenter', 'Manage topics');

        $isFeedback = $this->httpGet('isFeedback');
     
        $this->addBeforeRenderCallback(function() use ($isFeedback) {
            if($isFeedback) {
                $this->template->sidebar = $this->createFeedbackSidebar();
            } else {
                $this->template->sidebar = $this->createManageSidebar();
            }
        });
    }

    public function handleDeleteTopic(?FormResponse $fr = null) {
        global $app;

        $topicId = $this->httpGet('topicId', true);
        $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $topicLink = TopicEntity::createTopicProfileLink($topic, true);
            $userLink = UserEntity::createUserProfileLink($app->currentUser, true);
            
            $report = $app->reportRepository->getReportById($reportId);
            $reason = ReportCategory::toString($report->getCategory()) . ' (' . $report->getShortenedDescription(25) . '...)';

            $topicOwner = $app->topicMembershipManager->getTopicOwnerId($topicId);

            try {
                $app->topicRepository->beginTransaction();
                //$app->contentManager->deleteTopic($topicId);

                $app->topicManager->deleteTopic($topicId, $app->currentUser->getId());

                $app->notificationManager->createNewTopicDeleteDueToReportNotification($topicOwner, $topicLink, $userLink, $reason);

                $app->topicRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Topic #' . $topicId . ' deleted with all its posts and comments.', 'success');
            } catch(AException $e) {
                $app->topicRepository->rollback();

                $this->flashMessage('Could not delete topic. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $this->saveToPresenterCache('topic', $topic);

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageTopics', 'action' => 'deleteTopic', 'isSubmit' => '1', 'topicId' => $topicId, 'reportId' => $reportId])
                ->addSubmit('Delete topic \'' . $topic->getTitle() . '\'')
                ->addButton('Don\'t delete', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'');
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }
    
    public function renderDeleteTopic() {
        $topic = $this->loadFromPresenterCache('topic');
        $form = $this->loadFromPresenterCache('form');

        $this->template->topic_title = '\'' . $topic->getTitle() . '\'';
        $this->template->form = $form;
    }
}

?>