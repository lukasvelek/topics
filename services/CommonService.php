<?php

use App\Core\Application;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;

require_once('app/app_loader.php');
require_once('config.local.php');

try {
    $app = new Application();
} catch(Exception $e) {
    throw new ServiceException($e->getMessage(), $e);
} catch(AException $e) {
    throw $e;
}

?>