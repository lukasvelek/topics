<?php

use App\Exceptions\ServiceException;
use App\Services\OldGridExportCacheRemovingService;

require_once('CommonService.php');

global $app;

try {
    $service = new OldGridExportCacheRemovingService($app->logger, $app->serviceManager);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>