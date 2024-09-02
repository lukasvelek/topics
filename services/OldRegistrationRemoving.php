<?php

use App\Exceptions\ServiceException;
use App\Services\OldRegistrationConfirmationLinkRemovingService;

require_once('CommonService.php');

global $app;

try {
    $service = new OldRegistrationConfirmationLinkRemovingService($app->logger, $app->serviceManager, $app->userRegistrationRepository);
    $service->run();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
}

?>