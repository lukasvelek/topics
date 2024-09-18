<?php

namespace App\Managers;

use App\Core\Caching\CacheFactory;
use App\Logger\Logger;

abstract class AManager {
    protected Logger $logger;
    private ?EntityManager $entityManager;
    protected CacheFactory $cacheFactory;

    protected function __construct(Logger $logger, ?EntityManager $entityManager) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->cacheFactory = new CacheFactory($this->logger->getCfg());
    }

    public function createId(string $category) {
        if($this->entityManager !== null) {
            return $this->entityManager->generateEntityId($category);
        }

        return null;
    }
}

?>