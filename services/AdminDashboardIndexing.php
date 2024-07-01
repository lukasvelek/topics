<?php

use App\Core\Datetypes\DateTime;
use App\Exceptions\AException;

const SERVICE_TITLE = 'AdminDashboardIndexing';

require_once('CommonService.php');

startService();

try {
    $data = [];

    $data['mostActiveTopics'] = serialize(mostActiveTopics());
    $data['mostActivePosts'] = serialize(mostActivePosts());
    $data['mostActiveUsers'] = serialize(mostActiveUsers());

    updateData($data);
} catch(AException|Exception $e) {
    logError($e->getMessage());
}

stopService();

function updateData($data) {
    global $app;

    $qb = $app->topicRepository->getQb();

    $qb ->insert('admin_dashboard_widgets_graph_data', ['mostActiveTopics', 'mostActivePosts', 'mostActiveUsers'])
        ->values($data)
        ->execute()
        ->fetch();
}

function mostActiveTopics() {
    global $app;

    $age = new DateTime();
    $age->modify('-1d');
    $age = $age->getResult();

    $sql = "SELECT topicId, COUNT(postId) AS cnt FROM posts WHERE dateCreated >= '$age' GROUP BY topicId ORDER BY cnt DESC";

    $result = $app->topicRepository->sql($sql);

    $data = [];
    foreach($result as $row) {
        if(count($data) == 10) {
            break;
        }
        $data[$row['topicId']] = $row['cnt'];
    }

    return $data;
}

function mostActivePosts() {
    global $app;

    $age = new DateTime();
    $age->modify('-1d');
    $age = $age->getResult();

    $sql = "SELECT postId, COUNT(commentId) AS cnt FROM post_comments WHERE dateCreated >= '$age' GROUP BY postId ORDER BY cnt DESC";

    $result = $app->postRepository->sql($sql);

    $data = [];
    foreach($result as $row) {
        if(count($data) == 10) {
            break;
        }
        $data[$row['postId']] = $row['cnt'];
    }

    return $data;
}

function mostActiveUsers() {
    global $app;

    $age = new DateTime();
    $age->modify('-1d');
    $age = $age->getResult();

    $sql = "SELECT authorId, COUNT(commentId) AS cnt FROM post_comments WHERE dateCreated >= '$age' GROUP BY authorId ORDER BY cnt DESC";

    $result = $app->postRepository->sql($sql);

    $data = [];
    foreach($result as $row){
        if(count($data) == 10) {
            break;
        }
        $data[$row['authorId']] = $row['cnt'];
    }

    return $data;
}

?>