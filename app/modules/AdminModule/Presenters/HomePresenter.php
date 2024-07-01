<?php

namespace App\Modules\AdminModule;

class HomePresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createFeedbackSidebar();
        });
    }

    public function handleDashboard() {
        $this->addScript('mostActiveTopicsGraph(); mostActivePostsGraph();');
    }

    public function renderDashboard() {}

    public function actionGetMostActiveTopicsGraphData() {
        global $app;
        
        $qb = $app->topicRepository->getQb();
        $qb ->select(['*'])
            ->from('admin_dashboard_widgets_graph_data')
            ->limit(1)
            ->orderBy('dateCreated', 'DESC')
            ->execute();

        $data = [];
        while($row = $qb->fetchAssoc()) {
            $data = unserialize($row['mostActiveTopics']);
        }

        if(empty($data)) {
            $this->ajaxSendResponse(['error' => 'No data']);
            return;
        }

        $labels = [];
        $resultData = [];

        foreach($data as $topicId => $postCount) {
            $topic = $app->topicRepository->getTopicById($topicId);

            $labels[] = $topic->getTitle();
            $resultData[] = $postCount;
        }

        $this->ajaxSendResponse(['labels' => $labels, 'data' => $resultData]);
    }

    public function actionGetMostActivePostsGraphData() {
        global $app;
        
        $qb = $app->topicRepository->getQb();
        $qb ->select(['*'])
            ->from('admin_dashboard_widgets_graph_data')
            ->limit(1)
            ->orderBy('dateCreated', 'DESC')
            ->execute();

        $data = [];
        while($row = $qb->fetchAssoc()) {
            $data = unserialize($row['mostActivePosts']);
        }

        if(empty($data)) {
            $this->ajaxSendResponse(['error' => 'No data']);
            return;
        }

        $labels = [];
        $resultData = [];

        foreach($data as $postId => $commentCount) {
            $post = $app->postRepository->getPostById($postId);

            $labels[] = $post->getTitle();
            $resultData[] = $commentCount;
        }

        $this->ajaxSendResponse(['labels' => $labels, 'data' => $resultData]);
    }
}

?>