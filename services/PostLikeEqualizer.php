<?php

use App\Services\PostLikeEqualizerService;

require_once('CommonService.php');

global $app;

try {
    $service = new PostLikeEqualizerService($app->logger, $app->serviceManager, $app->postRepository);
    $service->run();
} catch(Exception $e) {
    $app->logger->error($e->getMessage(), 'PostLikeEqualizerService');
}

?>