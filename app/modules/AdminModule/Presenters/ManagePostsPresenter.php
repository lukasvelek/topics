<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
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
}

?>