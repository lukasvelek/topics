<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Constants\UserProsecutionType;
use App\Modules\APresenter;

class HomePresenter extends APresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {
        global $app;

        $followedTopicIds = $app->topicRepository->getFollowedTopicIdsForUser($app->currentUser->getId());
        $followedTopics = $app->topicRepository->bulkGetTopicsByIds($followedTopicIds);

        $posts = $app->postRepository->getLatestMostLikedPostsForTopicIds($followedTopicIds, 10);

        $postLister = new PostLister($app->userRepository, $app->topicRepository, $app->postRepository);

        $postLister->setPosts($posts);
        $postLister->setTopics($followedTopics);
        $postLister->shufflePosts();
        
        $this->saveToPresenterCache('postLister', $postLister);
        
        $permaFlashMessages = [];

        $userProsecution = $app->userProsecutionRepository->getLastProsecutionForUserId($app->currentUser->getId());

        if($userProsecution !== null) {
            if($userProsecution->getType() == UserProsecutionType::WARNING) {
                $permaFlashMessages[] = $this->createCustomFlashMessage('info', 'You have been warned for: ' . $userProsecution->getReason() . '.');
            }
        }

        $this->saveToPresenterCache('permaFlashMessages', $permaFlashMessages);
    }

    public function renderDashboard() {
        $postLister = $this->loadFromPresenterCache('postLister');
        $permaFlashMessages = $this->loadFromPresenterCache('permaFlashMessages');

        $this->template->title = 'Dashboard';
        $this->template->latest_posts = $postLister->render();
        $this->template->permanent_flash_messages = $permaFlashMessages;
    }
}

?>