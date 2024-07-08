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
        global $app;
        
        $qb = $app->topicRepository->getQb();
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
                    $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
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
                $post = $app->postRepository->getPostById($postId);
                try {
                    $topic = $app->topicManager->getTopicById($post->getTopicId(), $app->currentUser->getId());
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
                $user = $app->userRepository->getUserById($userId);

                $labels[] = $user->getUsername();
                $resultData[] = $commentCount;
            }

            $json['users']['labels'] = $labels;
            $json['users']['data'] = $resultData;
        }

        $this->ajaxSendResponse($json);
    }
}

?>