<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Core\CacheManager;
use App\UI\FormBuilder\FormBuilder;

class ManageTopicsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageTopicsPresenter', 'Manage topics');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function handleDeleteTopic() {
        global $app;

        $topicId = $this->httpGet('topicId', true);
        $topic = $app->topicRepository->getTopicById($topicId);
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $app->topicRepository->updateTopic($topicId, ['isDeleted' => '1']);

            $app->topicRepository->removeAllTopicFollows($topicId);

            $postIds = $app->postRepository->getPostIdsForTopicId($topicId);

            foreach($postIds as $postId) {
                $comments = $app->postCommentRepository->getCommentsForPostId($postId);

                foreach($comments as $comment) {
                    $app->postCommentRepository->updateComment($comment->getId(), ['isDeleted' => '1']);
                }

                $app->postRepository->updatePost($postId, ['isDeleted' => '1']);
            }

            CacheManager::invalidateCacheBulk(['topics', 'posts']);

            $this->flashMessage('Delete topic and all its posts with comments.', 'success');
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
        $this->template->form = $form->render();
    }
}

?>