<?php

namespace App\Modules\UserModule;

use App\Entities\TopicEntity;
use App\Entities\UserEntity;
use App\UI\LinkBuilder;

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
        
        $topics = [];
        while($row = $topicRows->fetchAssoc()) {
            $topics[] = TopicEntity::createEntityFromDbRow($row);
        }
        
        $topics = $app->topicManager->checkTopicsVisibility($topics, $app->currentUser->getId());

        $topicArray = [];
        if(!empty($topics)) {
            foreach($topics as $t) {
                $link = TopicEntity::createTopicProfileLink($t);
                
                $topicArray[] = $link;
            }
        }

        $createLink = LinkBuilder::createSimpleLink('create', ['page' => 'UserModule:Topics', 'action' => 'form', 'title' => $query], 'post-data-link');

        $topicResult = 'No topics found. But you can ' . $createLink . ' one!';

        if(!empty($topicArray)) {
            $topicResult = implode('<br>', $topicArray);
        }

        $this->saveToPresenterCache('topics', $topicResult);

        // tags
        $topicsDb = $app->topicRepository->composeQueryForTopics()->execute();

        $topics = [];
        while($row = $topicsDb->fetchAssoc()) {
            $topics[] = TopicEntity::createEntityFromDbRow($row);
        }

        $topics = $app->topicManager->checkTopicsVisibility($topics, $app->currentUser->getId());

        $topicIds = [];
        foreach($topics as $topic) {
            $topicIds[] = $topic->getId();
        }

        $tags = $app->topicRepository->searchTags(ucfirst($query), $topicIds);

        $tagArray = [];
        foreach($tags as $t) {
            $link = LinkBuilder::createSimpleLink($t, $this->createURL('tagTopics', ['tag' => $t, 'backQuery' => $query]), 'post-data-link');

            $tagArray[$t] = $link;
        }

        $tagResult = 'No tags found';
        if(!empty($tagArray)) {
            $tagResult = implode('<br>', $tagArray);
        }

        $this->saveToPresenterCache('tags', $tagResult);
    }

    public function renderSearch() {
        $users = $this->loadFromPresenterCache('users');
        $topics = $this->loadFromPresenterCache('topics');
        $tags = $this->loadFromPresenterCache('tags');

        $this->template->users = $users;
        $this->template->topics = $topics;
        $this->template->tags = $tags;
    }

    public function handleTagTopics() {
        global $app;

        $tag = $this->httpGet('tag', true);
        $backQuery = $this->httpGet('backQuery');

        $this->saveToPresenterCache('tagName', $tag);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('search', ['q' => $backQuery]), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);

        $topics = $app->topicRepository->getTopicsWithTag($tag);
        $topics = $app->topicManager->checkTopicsVisibility($topics, $app->currentUser->getId());

        $topicArray = [];
        foreach($topics as $t) {
            $topicArray[] = TopicEntity::createTopicProfileLink($t);
        }

        $this->saveToPresenterCache('topics', implode('<br>',  $topicArray));
    }

    public function renderTagTopics() {
        $links = $this->loadFromPresenterCache('links');
        $tagName = $this->loadFromPresenterCache('tagName');
        $topics = $this->loadFromPresenterCache('topics');

        $this->template->links = $links;
        $this->template->tag_name = $tagName;
        $this->template->topics = $topics;
    }
}

?>