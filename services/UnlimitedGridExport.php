<?php

use App\Exceptions\ServiceException;
use App\Services\UnlimitedGridExportService;

require_once('CommonService.php');

global $app;

try {
    $service = new UnlimitedGridExportService($app->logger, $app->serviceManager, $app->cfg, $app->gridExportRepository, $app->notificationManager, $app);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>