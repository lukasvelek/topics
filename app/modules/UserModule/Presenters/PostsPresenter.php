<?php

namespace App\Modules\UserModule;

use App\Exceptions\AException;
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
    }

    public function renderProfile() {
        $post = $this->loadFromPresenterCache('post');
        $comments = $this->loadFromPresenterCache('comments');
        $form = $this->loadFromPresenterCache('form');
        $topicLink = $this->loadFromPresenterCache('topic');

        $this->template->post_title = $topicLink . ' | ' . $post->getTitle();
        $this->template->post_text = $post->getText();
        $this->template->latest_comments = $comments;
        $this->template->new_comment_form = $form->render();
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

        try {
            $app->postCommentRepository->createNewComment($postId, $authorId, $text, $parentCommentId);
        } catch (AException $e) {
            $this->flashMessage('Comment could not be created. Error: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
        }
        
        $this->flashMessage('Comment posted.', 'success');
        $this->redirect(['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId]);
    }
}

?>