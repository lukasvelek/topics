<?php

use App\Core\Application;

chdir('..');
chdir('..');

require_once('app/app_loader.php');

function httpGet(mixed $key) {
    if(isset($_GET[$key])) {
        return htmlspecialchars($_GET[$key]);
    } else {
        return null;
    }
}

function httpPost(mixed $key) {
    if(isset($_POST[$key])) {
        return htmlspecialchars($_POST[$key]);
    } else {
        return null;
    }
}

$currentUserId = null;

if(isset($_GET['callingUserId'])) {
    $currentUserId = httpGet('callingUserId');
} else if(isset($_POST['callingUserId'])) {
    $currentUserId = httpPost('callingUserId');
}

$app = new Application();
$app->ajaxRun($currentUserId);

if($currentUserId == null) {
    echo('Calling user ID was not given!');
    exit;
}

$action = null;

if(isset($_GET['action'])) {
    $action = httpGet('action');
} else if(isset($_POST['action'])) {
    $action = httpPost('action');
}

if($action !== null) {
    if($action[0] == '_') {
        echo 'Cannot call internal functions from outside!';
        exit;
    }

    echo $app->logger->stopwatch(function() use ($action) {
        return $action();
    }, '(AJAX) ' . $action);
}

?>