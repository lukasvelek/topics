<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Core\CacheManager;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class ManagePostsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('ManagePostsPresenter', 'Manage posts');
     
        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        $sb->addLink('Users', ['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        $sb->addLink('User prosecution', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
        $sb->addLink('System status', ['page' => 'AdminModule:ManageSystemStatus', 'action' => 'list']);

        return $sb->render();
    }

    public function handleDeleteComment() {
        global $app;

        $commentId = $this->httpGet('commentId', true);
        $comment = $app->postCommentRepository->getCommentById($commentId);
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $app->postCommentRepository->updateComment($commentId, ['isDeleted' => '1']);

            $this->flashMessage('Comment deleted.', 'success');
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
        $this->template->form = $form->render();
    }

    public function handleDeletePost() {
        global $app;

        $postId = $this->httpGet('postId', true);
        $post = $app->postRepository->getPostById($postId);
        $reportId = $this->httpGet('reportId');

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $app->postRepository->updatePost($postId, ['isDeleted' => '1']);

            $comments = $app->postCommentRepository->getCommentsForPostId($postId);

            foreach($comments as $comment) {
                $app->postCommentRepository->updateComment($comment->getId(), ['isDeleted' => '1']);
            }

            CacheManager::invalidateCache('posts');

            $this->flashMessage('Post deleted with all its comments.', 'success');
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
        $this->template->form = $form->render();
    }
}

?>