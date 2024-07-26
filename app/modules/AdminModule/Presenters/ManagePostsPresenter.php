<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportCategory;
use App\Entities\UserEntity;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;
use Exception;

class ManagePostsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManagePostsPresenter', 'Manage posts');

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
        global $app;

        $commentId = $this->httpGet('commentId', true);
        $comment = $app->postCommentRepository->getCommentById($commentId);
        $reportId = $this->httpGet('reportId');
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $post = $app->postRepository->getPostById($comment->getPostId());
            $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'post-data-link');
            $userLink = UserEntity::createUserProfileLink($app->currentUser, true);
            
            $report = $app->reportRepository->getReportById($reportId);
            $reason = ReportCategory::toString($report->getCategory()) . ' (' . $report->getShortenedDescription(25) . '...)';

            
            try {
                $app->postRepository->beginTransaction();

                $app->contentManager->deleteComment($commentId);

                $app->notificationManager->createNewCommentDeleteDueToReportNotification($comment->getAuthorId(), $postLink, $userLink, $reason);

                $app->postRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Comment #' . $commentId . ' deleted.', 'success');
            } catch(Exception $e) {
                $app->postRepository->rollback();

                $this->flashMessage('Comment #' . $commentId . ' could not be deleted. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $this->saveToPresenterCache('comment', $comment);

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManagePosts', 'action' => 'deleteComment', 'commentId' => $commentId, 'isSubmit' => '1', 'reportId' => $reportId])
                ->addSubmit('Delete comment #' . $commentId)
                ->addButton('Don\'t delete', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'');
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
        global $app;

        $postId = $this->httpGet('postId', true);
        $post = $app->postRepository->getPostById($postId);
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $postLink = LinkBuilder::createSimpleLinkObject($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'post-data-link');
            $userLink = UserEntity::createUserProfileLink($app->currentUser, true);
            
            $report = $app->reportRepository->getReportById($reportId);
            $reason = ReportCategory::toString($report->getCategory()) . ' (' . $report->getShortenedDescription(25) . '...)';

            
            try {
                $app->postRepository->beginTransaction();

                $app->contentManager->deletePost($postId);

                $app->notificationManager->createNewPostDeleteDueToReportNotification($post->getAuthorId(), $postLink, $userLink, $reason);

                $app->postRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Post #' . $postId . ' deleted with all its comments.', 'success');
            } catch(Exception $e) {
                $app->postRepository->rollback();

                $this->flashMessage('Could not delete post. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $this->saveToPresenterCache('post', $post);

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManagePosts', 'action' => 'deletePost', 'postId' => $postId, 'isSubmit' => '1', 'reportId' => $reportId])
                ->addSubmit('Delete post \'' . $post->getTitle() . '\'')
                ->addButton('Don\'t delete', 'location.href = \'?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '\'');
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