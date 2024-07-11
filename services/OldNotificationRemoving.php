<?php

use App\Services\OldNotificationRemovingService;

require_once('CommonService.php');

global $app;

try {
    $service = new OldNotificationRemovingService($app->logger, $app->serviceManager, $app->notificationManager);
    $service->run();
} catch(Exception $e) {
    $app->logger->error($e->getMessage(), 'OldNotificationRemovingService');
}

?>