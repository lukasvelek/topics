<?php

namespace App\Managers;

use App\Logger\Logger;

abstract class AManager {
    protected Logger $logger;

    protected function __construct(Logger $logger) {
        $this->logger = $logger;
    }
}

?>