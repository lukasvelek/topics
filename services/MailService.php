<?php

use App\Exceptions\ServiceException;
use App\Services\MailService;

require_once('CommonService.php');

global $app;

try {
    $service = new MailService($app->logger, $app->serviceManager, $app->mailManager);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>