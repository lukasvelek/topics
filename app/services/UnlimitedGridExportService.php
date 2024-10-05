<?php

namespace App\Services;

use App\Core\Datetypes\DateTime;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use App\Logger\Logger;
use App\Managers\NotificationManager;
use App\Repositories\GridExportRepository;
use App\UI\GridBuilder\GridExporter;
use App\UI\LinkBuilder;
use Exception;

class UnlimitedGridExportService extends AService {
    private array $cfg;
    private GridExportRepository $ger;
    private NotificationManager $nm;

    public function __construct(Logger $logger, ServiceManager $serviceManager, array $cfg, GridExportRepository $ger, NotificationManager $nm) {
        parent::__construct('UnlimitedGridExport', $logger, $serviceManager);

        $this->cfg = $cfg;
        $this->ger = $ger;
        $this->nm = $nm;
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

                $ge = new GridExporter($this->logger, $hash, $this->cfg, $this->serviceManager);
                $ge->setExportAll();
                $result = $ge->export();

                if($result !== null) {
                    $result = str_replace('\\', '\\\\', $result);
                    $this->updateGridExportEntry($hash, $result);
                    $this->notifyUser($hash, $result);
                }

                $this->ger->commit(null, __METHOD__);
            } catch(AException $e) {
                $this->ger->rollback();

                $this->logError('Could not export \'' . $hash . '\'. Reason: ' . $e->getMessage());

                continue;
            }
        }
    }

    private function getAllExportHashes() {
        $maxCount = $this->cfg['MAX_GRID_EXPORT_SIZE'];

        return $this->ger->getWaitingUnlimitedExports($maxCount);
    }

    private function updateGridExportEntry(string $hash, string $filename) {
        $data = [
            'filename' => $filename,
            'dateFinished' => DateTime::now()
        ];

        return $this->ger->updateExportByHash($hash, $data);
    }

    private function notifyUser(string $hash, string $filename) {
        $export = $this->ger->getExportByHash($hash);
        if($export === null) {
            throw new ServiceException('No export with hash \'' . $hash . '\' exists.');
        }
        $userId = $export->getUserId();

        $link = new LinkBuilder();
        $link->setHref($this->cfg['APP_URL_BASE']. '/' . $filename)
            ->setClass('post-data-link')
            ->setText('here')
        ;

        $this->nm->createNewUnlimitedGridExportNotification($userId, $link->render());
    }
}

?>