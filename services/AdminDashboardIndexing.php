<?php

use App\Exceptions\ServiceException;
use App\Services\AdminDashboardIndexingService;

require_once('CommonService.php');

global $app;

try {
    $service = new AdminDashboardIndexingService($app->logger, $app->serviceManager, $app->topicRepository, $app->postRepository);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>