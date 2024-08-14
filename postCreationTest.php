<?php

use App\Core\Application;
use App\Core\Datetypes\DateTime;
use App\Managers\EntityManager;

require_once('config.local.php');
require_once('app/app_loader.php');

$app = new Application();

$max = 100000;

for($i = 0; $i < $max; $i++) {
    $postId = $app->postRepository->createEntityId(EntityManager::POSTS);

    $app->postRepository->createNewPost($postId, 'vYyotq2PLH9drHs4gUITnAPbIOJRCC7G', 'VkIhLJADwN09stwsO7Bm1CkkCuMhi0lL', 'test_' . $i, 'description', 'discussion', DateTime::now(), true);
}

?>