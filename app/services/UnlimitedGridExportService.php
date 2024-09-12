<?php

namespace App\Services;

use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Repositories\GridExportRepository;
use App\UI\GridBuilder\GridExporter;
use Exception;

class UnlimitedGridExportService extends AService {
    private array $cfg;
    private GridExportRepository $ger;
    private CacheManager $cache;

    public function __construct(Logger $logger, ServiceManager $serviceManager, array $cfg, GridExportRepository $ger) {
        parent::__construct('UnlimitedGridExport', $logger, $serviceManager);

        $this->cfg = $cfg;
        $this->ger = $ger;
        $this->cache = new CacheManager($logger);
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $hashes = $this->getAllExportHashes();
        
        foreach($hashes as $hash) {
            $data = $this->getExportDataFromCacheForHash($hash);

            $ge = new GridExporter($this->logger, $hash, $this->cfg, $this->serviceManager);
            $ge->setExportAll();
            $result = $ge->export();

            if($result !== null) {
                $result = str_replace('\\', '\\\\', $result);
                $this->updateGridExportEntry($hash, $result);
            }
        }
    }

    private function getAllExportHashes() {
        $maxCount = $this->cfg['MAX_GRID_EXPORT_SIZE'];

        return $this->ger->getWaitingUnlimitedExports($maxCount);
    }

    private function getExportDataFromCacheForHash(string $hash) {
        return $this->cache->loadCache($hash, function() { return []; }, CacheManager::NS_GRID_EXPORT_DATA, __METHOD__);
    }

    private function updateGridExportEntry(string $hash, string $filename) {
        $data = [
            'filename' => $filename,
            'dateFinished' => DateTime::now()
        ];

        $this->ger->updateExportByHash($hash, $data);
    }
}

?>