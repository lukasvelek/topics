<?php

use App\Core\Application;

require_once('app/app_loader.php');

$app = new Application();

if(!isset($_GET['page'])) {
    // default redirect address
}

try {
    $app->run();
} catch(Exception $e) {
    echo($e->__toString());
}

?>