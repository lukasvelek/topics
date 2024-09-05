<?php

session_start();

use App\Core\Application;
use App\Exceptions\AException;

require_once('app/app_loader.php');
require_once('config.local.php');

try {
    $app = new Application();
} catch(AException $e) {
    echo($e->getExceptionHTML());
} catch(Exception $e) {
    if($app->getIsDev()) {
        echo($e->__toString());
    } else {
        if($_GET['page'] != 'ErrorModule:E500') {
            $app->redirect(['page' => 'ErrorModule:E500']);
        }
    }
}

if(!isset($_GET['page'])) {
    // default redirect address
    $app->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
}

try {
    $app->run();
} catch(AException $e) {
    if($app->getIsDev()) {
        echo($e->getExceptionHTML());
    } else {
        if($_GET['page'] != 'ErrorModule:E500') {
            $app->redirect(['page' => 'ErrorModule:E500']);
        }
    }
} catch(Exception $e) {
    if($app->getIsDev()) {
        echo($e->__toString());
    } else {
        if($_GET['page'] != 'ErrorModule:E500') {
            $app->redirect(['page' => 'ErrorModule:E500']);
        }
    }
}

?>