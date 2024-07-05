<?php

use App\Core\Application;
use App\Exceptions\GeneralException;

require_once('app/app_loader.php');

$app = new Application();

_check();

function startService() {
    global $app;

    $app->serviceManager->startService(SERVICE_TITLE);
    logInfo('Service ' . SERVICE_TITLE . ' started.');
}

function stopService() {
    global $app;

    $app->serviceManager->stopService(SERVICE_TITLE);
    logInfo('Service ' . SERVICE_TITLE . ' ended.');
}

function logInfo(string $text) {
    global $app;

    $app->logger->serviceInfo($text, SERVICE_TITLE);
}

function logError(string $text) {
    global $app;

    $app->logger->serviceError($text, SERVICE_TITLE);
}

function _check() {
    global $app;

    if(!defined('SERVICE_TITLE')) {
        $app->logger->serviceError('No SERVICE_TITLE has been defined.', 'CommonService');

        throw new GeneralException('No SERVICE_TITLE has been defined.');
    }
}

?>