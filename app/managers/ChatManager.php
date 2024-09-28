<?php

namespace App\Managers;

use App\Logger\Logger;

class ChatManager extends AManager {
    public function __construct(Logger $logger, EntityManager $entityManager) {
        parent::__construct($logger, $entityManager);
    }
}

?>