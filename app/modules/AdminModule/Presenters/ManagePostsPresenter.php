<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportCategory;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;
use Exception;

class ManagePostsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManagePostsPresenter', 'Manage posts');
    }
    
    public function startup() {
        parent::startup();
        
        $isFeedback = $this->httpGet('isFeedback');
        $this->addBeforeRenderCallback(function() use ($isFeedback) {
            if($isFeedback) {
                $this->template->sidebar = $this->createFeedbackSidebar();
            } else {
                $this->template->sidebar = $this->createManageSidebar();
            }
        });
    }

    public function handleDeleteComment(?FormResponse $fr = null) {
        $commentId = $this->httpGet('commentId', true);
        $comment = $this->app->postCommentRepository->getCommentById($commentId);
        $reportId = $this->httpGet('reportId');
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            try {
                $post = $this->app->postManager->getPostById($this->getUserId(), $comment->getPostId());
            } catch(AException $e) {
                $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
            }
            $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'post-data-link');
            $userLink = UserEntity::createUserProfileLink($this->getUser(), true);
            
            $report = $this->app->reportRepository->getReportById($reportId);
            $reason = ReportCategory::toString($report->getCategory()) . ' (' . $report->getShortenedDescription(25) . '...)';

            try {
                $this->app->postRepository->beginTransaction();

                $this->app->contentManager->deleteComment($commentId);

                $this->app->notificationManager->createNewCommentDeleteDueToReportNotification($comment->getAuthorId(), $postLink, $userLink, $reason);

                $this->app->postRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Comment #' . $commentId . ' deleted.', 'success');
            } catch(Exception $e) {
                $this->app->postRepository->rollback();

                $this->flashMessage('Comment #' . $commentId . ' could not be deleted. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $this->saveToPresenterCache('comment', $comment);

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManagePosts', 'action' => 'deleteComment', 'commentId' => $commentId, 'isSubmit' => '1', 'reportId' => $reportId])
                ->addSubmit('Delete comment #' . $commentId)
                ->addButton('Don\'t delete', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'', 'formSubmit');
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeleteComment() {
        $comment = $this->loadFromPresenterCache('comment');
        $form = $this->loadFromPresenterCache('form');

        $this->template->comment_id = $comment->getId();
        $this->template->form = $form;
    }

    public function handleDeletePost(?FormResponse $fr = null) {
        $postId = $this->httpGet('postId', true);
        try {
            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
        } catch(AException $e) {
            $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'AdminModule:Home', 'action' => 'dashboard']);
        }
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'post-data-link');
            $userLink = UserEntity::createUserProfileLink($this->getUser(), true);
            
            $report = $this->app->reportRepository->getReportById($reportId);
            $reason = ReportCategory::toString($report->getCategory()) . ' (' . $report->getShortenedDescription(25) . '...)';

            try {
                $this->app->postRepository->beginTransaction();

                $this->app->contentManager->deletePost($postId);

                $this->app->notificationManager->createNewPostDeleteDueToReportNotification($post->getAuthorId(), $postLink, $userLink, $reason);

                $this->app->postRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Post #' . $postId . ' deleted with all its comments.', 'success');
            } catch(Exception $e) {
                $this->app->postRepository->rollback();

                $this->flashMessage('Could not delete post. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $this->saveToPresenterCache('post', $post);

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManagePosts', 'action' => 'deletePost', 'postId' => $postId, 'isSubmit' => '1', 'reportId' => $reportId])
                ->addSubmit('Delete post \'' . $post->getTitle() . '\'')
                ->addButton('Don\'t delete', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'', 'formSubmit');
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeletePost() {
        $post = $this->loadFromPresenterCache('post');
        $form = $this->loadFromPresenterCache('form');

        $this->template->post_title = '\'' . $post->getTitle() . '\'';
        $this->template->form = $form;
    }
}

?>