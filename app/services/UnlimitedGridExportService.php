<?php

namespace App\Services;

use App\Core\Application;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\GridExportException;
use App\Exceptions\ServiceException;
use App\Logger\Logger;
use App\Managers\NotificationManager;
use App\Repositories\GridExportRepository;
use App\UI\GridBuilder2\GridExportHandler;
use App\UI\LinkBuilder;
use Exception;

class UnlimitedGridExportService extends AService {
    private array $cfg;
    private GridExportRepository $ger;
    private NotificationManager $nm;
    private Cache $cache;
    private Application $app;

    public function __construct(Logger $logger, ServiceManager $serviceManager, array $cfg, GridExportRepository $ger, NotificationManager $nm, Application $app) {
        parent::__construct('UnlimitedGridExport', $logger, $serviceManager);

        $this->cfg = $cfg;
        $this->ger = $ger;
        $this->nm = $nm;
        $this->app = $app;

        $this->cache = $this->cacheFactory->getCache(CacheNames::GRID_EXPORTS);
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
            try {
                $this->logInfo('Starting export \'' . $hash . '\'.');
                $this->ger->beginTransaction();

                $cacheResult = $this->cache->load($hash, function() {
                    return null;
                });

                if($cacheResult === null) {
                    throw new GridExportException('Could not find export data for \'' . $hash . '\'.');
                }

                try {
                    $userId = $this->getExportAuthor($hash);
                } catch(AException) {
                    $userId = null;
                }

                $geh = GridExportHandler::createForAsync($cacheResult, $this->app, $this->cfg, $userId);
                try {
                    [$file, $hash] = $geh->exportNow($hash);
                } catch(AException $e) {
                    throw new GridExportException('Could not export data.', $e);
                }

                if($file !== null) {
                    $this->notifyUser($hash, $file);
                } else {
                    throw new GridExportException('No data has been exported.');
                }

                $this->logInfo('Finished export \'' . $hash . '\'.');

                $this->ger->commit(null, __METHOD__);
            } catch(AException|Exception $e) {
                $this->ger->rollback();

                $this->logError('Could not export \'' . $hash . '\'. Reason: ' . $e->getMessage());

                continue;
            }
        }
    }

    private function getAllExportHashes() {
        return $this->ger->getWaitingUnlimitedExports();
    }

    private function getExportAuthor(string $hash) {
        $export = $this->ger->getExportByHash($hash);
        if($export === null) {
            throw new ServiceException('No export with hash \'' . $hash . '\' exists.');
        }
        return $export->getUserId();
    }

    private function notifyUser(string $hash, string $filename) {
        $userId = $this->getExportAuthor($hash);

        $link = new LinkBuilder();
        $link->setHref($this->cfg['APP_URL_BASE']. '/' . $filename)
            ->setClass('post-data-link')
            ->setText('here')
        ;

        $this->nm->createNewUnlimitedGridExportNotification($userId, $link->render());
    }
}

?>