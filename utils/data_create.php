<?php

session_start();

use App\Constants\TopicMemberRole;
use App\Core\Application;
use App\Entities\TopicTagEntity;
use App\Exceptions\AException;
use App\Helpers\ColorHelper;
use App\Managers\EntityManager;

require_once('app/app_loader.php');
require_once('config.local.php');

try {
    $app = new Application();
} catch(AException $e) {
    echo($e->getExceptionHTML());
    exit;
} catch(Exception $e) {
    header('Location: ?page=ErrorModule:E500');
    exit;
}

try {

    $maxTopics = 1000;
    $maxPosts = 1000;

    for($topics = 0; $topics < $maxTopics; $topics++) {
        $topicId = $app->entityManager->generateEntityId(EntityManager::TOPICS);
        $admin = $app->userManager->getUserByUsername('admin');

        $tags = [
            'test',
            'auto generated'
        ];

        $tagArray = [];
        $rawTagsArray = [];
        foreach($tags as $tag) {
            $tag = trim($tag);
            $tag = ucfirst($tag);

            $rawTagsArray[] = $tag;

            [$fg, $bg] = ColorHelper::createColorCombination();
            $tte = new TopicTagEntity($tag, $fg, $bg);

            $tagArray[] = $tte;
        }

        $tags = serialize($tagArray);
        $rawTags = implode(',', $rawTagsArray);

        $tags = str_replace('\\', '\\\\', $tags);

        $app->topicRepository->createNewTopic($topicId, 'topic_' . ($topics + 1), 'test', $tags, false, $rawTags);
        $app->topicMembershipManager->followTopic($topicId, $admin->getId());
        $app->topicMembershipManager->changeRole($topicId, $admin->getId(), $admin->getId(), TopicMemberRole::OWNER);

        for($posts = 0; $posts < $maxPosts; $posts++) {
            $postId = $app->entityManager->generateEntityId(EntityManager::POSTS);

            $app->postRepository->createNewPost($postId, $topicId, $admin->getId(), 'post_' . ($posts + 1), 'test', 'discussion', date('Y-m-d H:i:s'), true);
        }
    }

} catch(AException|Exception $e) {
    echo $e->getMessage();
    exit;
}

?>