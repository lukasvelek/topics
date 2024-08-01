<?php

namespace App\Managers;

use App\Logger\Logger;

class MailManager extends AManager {
    public function __construct(Logger $logger) {
        parent::__construct($logger);
    }
}

?>