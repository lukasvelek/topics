<?php

use App\Core\Application;
use App\Exceptions\AException;

session_start();

require_once('config.local.php');

try {
    require_once('app/app_loader.php');

    $app = new Application();

    $app->run();
} catch(AException $e) {
    echo($e->getMessage());
    exit;
}

?>