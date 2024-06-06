<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class TopicsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('TopicsPresenter', 'Topics');
    }

    public function handleProfile() {
        global $app;

        $topicId = $this->httpGet('topicId');

        // topic info
        $topic = $app->topicRepository->getTopicById($topicId);

        $this->saveToPresenterCache('topic', $topic);

        // posts
        $posts = $app->postRepository->getLatestPostsForTopicId($topicId, 0);

        $postLister = new PostLister($app->userRepository, $app->topicRepository, $app->postRepository);
        $postLister->setPosts($posts);
        $postLister->setTopics([$topic]);
        $postLister->setTopicLinkHidden();

        $this->saveToPresenterCache('postLister', $postLister);

        // topic data
        $manager = $app->userRepository->getUserById($topic->getManagerId());

        $managerLink = '<a class="post-data-link" href="' . $app->composeURL(['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $manager->getId()]) . '">' . $manager->getUsername() . '</a>';

        $topicFollowers = $app->topicRepository->getFollowersForTopicId($topicId);
        $postCount = $app->postRepository->getPostCountForTopicId($topicId);

        $code = '
            <p class="post-data">Followers: ' . count($topicFollowers) . '</p>
            <p class="post-data">Manager: ' . $managerLink . '</p>
            <p class="post-data">Topic started on: ' . $topic->getDateCreated() . '</p>
            <p class="post-data">Posts: ' . $postCount . '</p>
        ';

        $this->saveToPresenterCache('postData', $code);

        // new post form
        $fb = new FormBuilder();

        $fb ->setAction(['page' => 'UserModule:Topics', 'action' => 'newPost', 'topicId' => $topicId])
            ->addTextInput('title', 'Title:', null, true)
            ->addTextArea('text', 'Text:', null, true)
            ->addSubmit('Post')
        ;

        $this->saveToPresenterCache('newPostForm', $fb);
    }

    public function renderProfile() {
        $topic = $this->loadFromPresenterCache('topic');
        $postLister = $this->loadFromPresenterCache('postLister');
        $postData = $this->loadFromPresenterCache('postData');
        $fb = $this->loadFromPresenterCache('newPostForm');

        $this->template->topic_title = $topic->getTitle();
        $this->template->topic_description = $topic->getDescription();
        $this->template->latest_posts = $postLister->render();
        $this->template->topic_data = $postData;
        $this->template->new_post_form = $fb->render();
    }

    public function handleNewPost() {
        global $app;

        $title = $this->httpPost('title');
        $text = $this->httpPost('text');
        $userId = $app->currentUser->getId();
        $topicId = $this->httpGet('topicId');

        $app->postRepository->createNewPost($topicId, $userId, $title, $text);

        $this->flashMessage('Post created.', 'success');
        $app->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }
}

?>