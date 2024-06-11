<?php

namespace App\Modules\UserModule;

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

        $author = $app->userRepository->getUserById($post->getAuthorId());
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $post->getAuthorId() . '">' . $author->getUsername() . '</a>';

        $postData = '
            <div>
                <p class="post-data">Likes: ' . $likes . ' ' . ($liked ? $unlikeLink : $likeLink) . '</p>
                <p class="post-data">Date posted: ' . DateTimeFormatHelper::formatDateToUserFriendly($post->getDateCreated()) . '</p>
                <p class="post-data">Author: ' . $authorLink . '</p>
            </div>
        ';

        $this->saveToPresenterCache('postData', $postData);
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