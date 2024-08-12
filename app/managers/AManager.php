<?php

namespace App\Managers;

use App\Logger\Logger;

abstract class AManager {
    protected Logger $logger;
    private ?EntityManager $entityManager;

    protected function __construct(Logger $logger, ?EntityManager $entityManager) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    public function createId(string $category) {
        if($this->entityManager !== null) {
            return $this->entityManager->generateEntityId($category);
        }

        return null;
    }
}

?>