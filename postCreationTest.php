<?php

use App\Core\Application;
use App\Core\Datetypes\DateTime;
use App\Managers\EntityManager;

require_once('config.local.php');
require_once('app/app_loader.php');

$app = new Application();

$max = 100000;

$topicIds = [
    'iLvwWPmRwMkGLvH4zTuX2lEjpUtb1sSx',
    'uD0Gqleml3oFGMaaoWHfodOpB0SJyjQu',
    'Lm5n461TYqPNt1ZeQpL0sauzxPhwsf3p',
    'jw1CpYaweEZ5CO2ENML5OWwSLjZvOHhx',
    'ctS48IuyLXVWHP2pC0SzPqw9Hj6Hw8DA',
    'ndN6gLnBGIrz9nHsJ8SltyXfff48EZ3o',
    'q7h0UmU5uHePfGSOcUPQglF4GzG9PLqD'
];

for($i = 0; $i < $max; $i++) {
    $postId = $app->postRepository->createEntityId(EntityManager::POSTS);

    if(count($topicIds) > 1) {
        $r = rand(0, count($topicIds) - 1);
    } else {
        $r = 0;
    }

    $app->postRepository->createNewPost($postId, $topicIds[$r], 'TibfFG80ZMrBsEiYdDCUyUm6wYVQSbfp', 'test_' . $i, 'description', 'discussion', DateTime::now(), true);
}

?>