<?php

namespace App\Services;

use App\Core\CacheManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use Exception;

class OldGridExportCacheRemovingService extends AService {
    public function __construct(Logger $logger, ServiceManager $serviceManager) {
        parent::__construct('OldGridExportCacheRemoving', $logger, $serviceManager);
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            try {
                $this->serviceStop();
            } catch(AException|Exception $e2) {}

            $this->logError($e->getMessage());
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $cache = new CacheManager($this->logger);

        if(!$cache->invalidateCacheBulk([CacheManager::NS_GRID_EXPORT_DATA, CacheManager::NS_GRID_EXPORTS])) {
            throw new GeneralException('Could not invalidate cache.');
        }
    }
}

?>