<?php

session_start();

use App\Core\Application;
use App\Exceptions\ApplicationInitializationException;

require_once('app/app_loader.php');

try {
    $app = new Application();
} catch(Exception $e) {
    throw new ApplicationInitializationException($e->getMessage() . '<br>' . $e->getTraceAsString());
}

if(!isset($_GET['page'])) {
    // default redirect address
    $app->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
}

try {
    $app->run();
} catch(Exception $e) {
    echo($e->__toString());
}

?>