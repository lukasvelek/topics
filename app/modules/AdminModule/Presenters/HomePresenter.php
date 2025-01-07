<?php

namespace App\Modules\AdminModule;

use App\Exceptions\AException;

class HomePresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {
        $this->addScript('createDashboard();');
        $this->addScript('autoUpdateCounter();');

        $this->saveToPresenterCache('refreshLink', '<a class="post-data-link" href="#" onclick="autoUpdate()">Refresh</a>');
    }

    public function renderDashboard() {
        $refreshLink = $this->loadFromPresenterCache('refreshLink');

        $this->template->refresh_link = $refreshLink;
    }

    public function actionGetGraphData() {
        $qb = $this->app->topicRepository->getQb();
        $qb ->select(['*'])
            ->from('admin_dashboard_widgets_graph_data')
            ->limit(1)
            ->orderBy('dateCreated', 'DESC')
            ->execute();

        $json = [];

        $topics = [];
        $posts = [];
        $users = [];
        while($row = $qb->fetchAssoc()) {
            $topics = unserialize($row['mostActiveTopics']);
            $posts = unserialize($row['mostActivePosts']);
            $users = unserialize($row['mostActiveUsers']);
        }

        // topics
        if(empty($topics)) {
            $json['topics']['error'] = 'No data currently available';
        } else {
            $labels = [];
            $resultData = [];

            foreach($topics as $topicId => $postCount) {
                try {
                    $topic = $this->app->topicManager->getTopicById($topicId, $this->getUserId());
                } catch(AException $e) {
                    continue;
                }
    
                $labels[] = $topic->getTitle();
                $resultData[] = $postCount;
            }

            $json['topics']['labels'] = $labels;
            $json['topics']['data'] = $resultData;
        }

        // posts
        if(empty($posts)) {
            $json['posts']['error'] = 'No data currently available';
        } else {
            $labels = [];
            $resultData = [];

            foreach($posts as $postId => $commentCount) {
                try {
                    $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
                    $topic = $this->app->topicManager->getTopicById($post->getTopicId(), $this->getUserId());
                } catch(AException $e) {
                    continue;
                }

                $labels[] = '[' . $topic->getTitle() . '] ' . $post->getTitle();
                $resultData[] = $commentCount;
            }

            $json['posts']['labels'] = $labels;
            $json['posts']['data'] = $resultData;
        }

        // users
        if(empty($users)) {
            $json['users']['error'] = 'No data currently available';
        } else {
            $labels = [];
            $resultData = [];

            foreach($users as $userId => $commentCount) {
                try {
                    $user = $this->app->userManager->getUserById($userId);
                } catch(AException $e) {
                    continue;
                }

                $labels[] = $user->getUsername();
                $resultData[] = $commentCount;
            }

            $json['users']['labels'] = $labels;
            $json['users']['data'] = $resultData;
        }

        return $json;
    }
}

?>