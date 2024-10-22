<?php

session_start();

use App\Core\Application;
use App\Exceptions\AException;

require_once('config.local.php');
require_once('app/app_loader.php');

try {
    $app = new Application();
} catch(AException $e) {
    echo($e->getExceptionHTML());
    exit;
} catch(Exception $e) {
    header('Location: ?page=ErrorModule:E500');
    exit;
}

if(!isset($_GET['page'])) {
    // default redirect address
    if($app !== null) {
        $app->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
    } else {
        header('Location: ?page=AnonymModule:Login&action=checkLogin');
    }
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