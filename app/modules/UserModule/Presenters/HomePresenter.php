<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Modules\APresenter;

class HomePresenter extends APresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {
        global $app;

        $followedTopicIds = $app->topicRepository->getFollowedTopicIdsForUser($app->currentUser->getId());
        $followedTopics = $app->topicRepository->bulkGetTopicsByIds($followedTopicIds);

        $posts = $app->postRepository->getLatestMostLikedPostsForTopicIds($followedTopicIds, 5);

        $postLister = new PostLister($app->userRepository, $app->topicRepository, $app->postRepository);

        $postLister->setPosts($posts);
        $postLister->setTopics($followedTopics);
        
        $this->saveToPresenterCache('postLister', $postLister);
    }

    public function renderDashboard() {
        $postLister = $this->loadFromPresenterCache('postLister');

        $this->template->title = 'Dashboard';
        $this->template->latest_posts = $postLister->render();
    }
}

?>