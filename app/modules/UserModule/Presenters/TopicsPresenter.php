<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Modules\APresenter;

class TopicsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('TopicsPresenter', 'Topics');
    }

    public function handleProfile() {
        global $app;

        $topicId = $this->httpGet('topicId');

        $topic = $app->topicRepository->getTopicById($topicId);

        $this->saveToPresenterCache('topic', $topic);

        $posts = $app->postRepository->getLatestPostsForTopicId($topicId, 0);

        $postLister = new PostLister($app->userRepository, $app->topicRepository, $app->postRepository);
        $postLister->setPosts($posts);
        $postLister->setTopics([$topic]);
        $postLister->setTopicLinkHidden();

        $this->saveToPresenterCache('postLister', $postLister);

        // post data
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
    }

    public function renderProfile() {
        $topic = $this->loadFromPresenterCache('topic');
        $postLister = $this->loadFromPresenterCache('postLister');
        $postData = $this->loadFromPresenterCache('postData');

        $this->template->topic_title = $topic->getTitle();
        $this->template->topic_description = $topic->getDescription();
        $this->template->latest_posts = $postLister->render();
        $this->template->topic_data = $postData;
    }
}

?>