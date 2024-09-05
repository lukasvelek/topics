<?php

use App\Exceptions\ServiceException;
use App\Services\OldNotificationRemovingService;

require_once('CommonService.php');

global $app;

try {
    $service = new OldNotificationRemovingService($app->logger, $app->serviceManager, $app->notificationManager);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>