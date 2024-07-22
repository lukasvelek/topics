<?php

namespace App\Modules\UserModule;

use App\Entities\TopicEntity;
use App\Entities\UserEntity;

class SearchPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('SearchPresenter', 'Search');
    }

    public function handleSearch() {
        global $app;

        $query = $this->httpGet('q', true);

        // users
        $users = $app->userRepository->searchUsersByUsername($query);

        $usersArray = [];
        foreach($users as $user) {
            $link = UserEntity::createUserProfileLink($user);

            $usersArray[] = $link;
        }

        $userResult = 'No users found';
        if(!empty($usersArray)) {
            $userResult = implode('<br>', $usersArray);
        }

        $this->saveToPresenterCache('users', $userResult);

        // topics
        $topicRows = $app->topicRepository->composeQueryForTopicsSearch($query)->execute();

        $topicArray = [];
        while($row = $topicRows->fetchAssoc()) {
            $topic = TopicEntity::createEntityFromDbRow($row);
            $link = TopicEntity::createTopicProfileLink($topic);

            $topicArray[] = $link;
        }

        $topicResult = 'No topics found';
        if(!empty($topicArray)) {
            $topicResult = implode('<br>', $topicArray);
        }

        $this->saveToPresenterCache('topics', $topicResult);
    }

    public function renderSearch() {
        $users = $this->loadFromPresenterCache('users');
        $topics = $this->loadFromPresenterCache('topics');

        $this->template->users = $users;
        $this->template->topics = $topics;
    }
}

?>