<?php

use App\Exceptions\ServiceException;
use App\Services\HashtagTrendsIndexingService;

require_once('CommonService.php');

global $app;

try {
    $service = new HashtagTrendsIndexingService($app->logger, $app->serviceManager, $app->postManager);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>