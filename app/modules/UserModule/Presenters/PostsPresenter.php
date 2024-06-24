<?php

namespace App\Modules\UserModule;

use App\Constants\ReportCategory;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class PostsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('PostsPresenter', 'Posts');
    }

    public function handleProfile() {
        global $app;

        $postId = $this->httpGet('postId');
        $post = $app->postRepository->getPostById($postId);

        if(!$app->visibilityAuthorizator->canViewDeletedPost($app->currentUser->getId())) {
            $this->flashMessage('This post does not exist.', 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $post->getTopicId()]);
        }

        $this->saveToPresenterCache('post', $post);

        $this->saveToPresenterCache('comments', '<script type="text/javascript">loadCommentsForPost(' . $postId . ', 10, 0, ' . $app->currentUser->getId() . ')</script><div id="comments-list"></div><div id="comments-list-link"></div><br>');

        $parentCommentId = $this->httpGet('parentCommentId');

        // new comment form
        $fb = new FormBuilder();

        $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'newComment', 'postId' => $postId, 'parentCommentId' => $parentCommentId])
            ->addTextArea('text', 'Comment:', null, true)
            ->addSubmit('Post')
        ;

        $this->saveToPresenterCache('form', $fb);

        $topic = $app->topicRepository->getTopicById($post->getTopicId());
        $topicLink = '<a class="post-title-link" href="?page=UserModule:Topics&action=profile&topicId=' . $topic->getId() . '">' . $topic->getTitle() . '</a>';

        $this->saveToPresenterCache('topic', $topicLink);

        // post data
        $likes = $post->getLikes();
        $likeLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=like&postId=' . $postId . '">Like</a>';
        $unlikeLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=unlike&postId=' . $postId . '">Unlike</a>';
        $liked = $app->postRepository->checkLike($app->currentUser->getId(), $postId);
        $finalLikeLink = '';

        if(!$post->isDeleted()) {
            $finalLikeLink = ' ' . ($liked ? $unlikeLink : $likeLink);
        }

        $author = $app->userRepository->getUserById($post->getAuthorId());
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $post->getAuthorId() . '">' . $author->getUsername() . '</a>';

        $reportLink = '';
        if(!$post->isDeleted()) {
            $reportLink = '<a class="post-data-link" href="?page=UserModule:Posts&action=reportForm&postId=' . $postId . '">Report post</a>';
        }

        $deleteLink = '';

        if($app->actionAuthorizator->canDeletePost($app->currentUser->getId()) && !$post->isDeleted()) {
            $deleteLink = '<p class="post-data"><a class="post-data-link" href="?page=UserModule:Posts&action=deletePost&postId=' . $postId . '">Delete post</a></p>';
        } else if($post->isDeleted()) {
            $deleteLink = '<p class="post-data">Post deleted</p>';
        }

        $postData = '
            <div>
                <p class="post-data">Likes: ' . $likes . '' . $finalLikeLink . '</p>
                <p class="post-data">Date posted: ' . DateTimeFormatHelper::formatDateToUserFriendly($post->getDateCreated()) . '</p>
                <p class="post-data">Author: ' . $authorLink . '</p>
                <p class="post-data">' . $reportLink . '</p>
                ' . $deleteLink . '
            </div>
        ';

        $this->saveToPresenterCache('postData', $postData);

        if($post->isDeleted()) {
            $this->addExternalScript('js/Reducer.js');
            $this->addScript('reducePostProfile()');
        }
    }

    public function renderProfile() {
        $post = $this->loadFromPresenterCache('post');
        $comments = $this->loadFromPresenterCache('comments');
        $form = $this->loadFromPresenterCache('form');
        $topicLink = $this->loadFromPresenterCache('topic');
        $postData = $this->loadFromPresenterCache('postData');

        $this->template->post_title = $topicLink . ' | ' . $post->getTitle();
        $this->template->post_text = $post->getText();
        $this->template->latest_comments = $comments;
        $this->template->new_comment_form = $form->render();
        $this->template->post_data = $postData;
    }

    public function handleNewComment() {
        global $app;

        $postId = $this->httpGet('postId');
        $authorId = $app->currentUser->getId();
        $text = $this->httpPost('text');
        $parentCommentId = $this->httpGet('parentCommentId');

        $matches = [];
        preg_match_all("/[@]\w*/m", $text, $matches);

        $matches = $matches[0];

        $users = [];
        foreach($matches as $match) {
            $username = substr($match, 1);
            $user = $app->userRepository->getUserByUsername($username);
            $link = '<a class="post-text-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">@' . $username . '</a>';
            
            $users[$match] = $link;
        }

        foreach($users as $k => $v) {
            $text = str_replace($k, $v, $text);
        }

        $pattern = "/\[(.*?),\s*(https?:\/\/[^\]]+)\]/";
        $replacement = '<a class="post-text-link" href="$2" target="_blank">$1</a>';

        $text = preg_replace($pattern, $replacement, $text);

        try {
            $app->postCommentRepository->createNewComment($postId, $authorId, $text, $parentCommentId);
        } catch (AException $e) {
            $this->flashMessage('Comment could not be created. Error: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
        }
        
        $this->flashMessage('Comment posted.', 'success');
        $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
    }

    public function handleReportForm() {
        global $app;

        $postId = $this->httpGet('postId');
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $this->httpPost('category');
            $description = $this->httpPost('description');
            $userId = $app->currentUser->getId();

            $app->reportRepository->createPostReport($userId, $postId, $category, $description);

            $this->flashMessage('Post reported.', 'success');
            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
        } else {
            $post = $app->postRepository->getPostById($postId);
            $this->saveToPresenterCache('post', $post);

            $categories = ReportCategory::getArray();
            $categoryArray = [];
            foreach($categories as $k => $v) {
                $categoryArray[] = [
                    'value' => $k,
                    'text' => $v
                ];
            }

            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'reportForm', 'isSubmit' => '1', 'postId' => $postId])
                ->addSelect('category', 'Category:', $categoryArray, true)
                ->addTextArea('description', 'Additional notes:', null, true)
                ->addSubmit('Send')
                ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderReportForm() {
        $post = $this->loadFromPresenterCache('post');
        $form = $this->loadFromPresenterCache('form');

        $this->template->post_title = $post->getTitle();
        $this->template->form = $form->render();
    }

    public function handleReportComment() {
        global $app;

        $commentId = $this->httpGet('commentId');
        $comment = $app->postCommentRepository->getCommentById($commentId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $this->httpPost('category');
            $description = $this->httpPost('description');
            $userId = $app->currentUser->getId();

            $app->reportRepository->createCommentReport($userId, $commentId, $category, $description);

            $this->flashMessage('Comment reported.', 'success');
            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $comment->getPostId()]);
        } else {
            $this->saveToPresenterCache('comment', $comment);

            $categories = ReportCategory::getArray();
            $categoryArray = [];
            foreach($categories as $k => $v) {
                $categoryArray[] = [
                    'value' => $k,
                    'text' => $v
                ];
            }

            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'reportComment', 'isSubmit' => '1', 'commentId' => $commentId])
                ->addSelect('category', 'Category:', $categoryArray, true)
                ->addTextArea('description', 'Additional notes:', null, true)
                ->addSubmit('Send')
                ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderReportComment() {
        $comment = $this->loadFromPresenterCache('comment');
        $form = $this->loadFromPresenterCache('form');

        $this->template->comment_id = $comment->getId();
        $this->template->form = $form->render();
    }

    public function handleDeleteComment() {
        global $app;

        $commentId = $this->httpGet('commentId');
        $postId = $this->httpGet('postId');

        if($this->httpGet('isSubmit') == '1') {
            $app->contentManager->deleteComment($commentId);

            $this->flashMessage('Comment #' . $commentId . ' has been deleted.', 'success');
            $this->redirect(['action' => 'profile', 'postId' => $postId]);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'deleteComment', 'isSubmit' => '1', 'commentId' => $commentId, 'postId' => $postId])
                ->addSubmit('Delete comment')
                ->addButton('&larr; Go back', 'location.href = \'?page=UserModule:Posts&action=profile&postId=' . $postId . '\';')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeleteComment() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }

    public function handleDeletePost() {
        global $app;

        $postId = $this->httpGet('postId');

        if($this->httpGet('isSubmit') == '1') {
            $app->contentManager->deletePost($postId);

            $this->flashMessage('Post #' . $postId . ' has been deleted.', 'success');
            $this->redirect(['action' => 'profile', 'postId' => $postId]);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'deletePost', 'isSubmit' => '1', 'postId' => $postId])
                ->addSubmit('Delete post')
                ->addButton('&larr; Go back', 'location.href = \'?page=UserModule:Posts&action=profile&postId=' . $postId . '\';')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeletePost() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }
}

?>