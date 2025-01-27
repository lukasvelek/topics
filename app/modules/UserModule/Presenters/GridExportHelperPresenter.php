<?php

namespace App\Modules\UserModule;

use App\Core\Datetypes\DateTime;
use App\Exceptions\AException;
use App\Exceptions\AjaxRequestException;
use App\Exceptions\GeneralException;
use App\UI\GridBuilder\GridExporter;

class GridExportHelperPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('GridExportHelperPresenter', 'Grid Export Helper');
    }

    public function startup() {
        parent::startup();
    }

    public function actionExportGrid() {
        $hash = $this->httpGet('hash', true);
        $exportAll = $this->httpGet('exportAll', true);
        $gridName = $this->httpGet('gridName', true);

        $exportAll = ($exportAll == 'true');

        try {
            $this->app->gridExportRepository->beginTransaction();

            $dbResult = $this->app->gridExportRepository->createNewExport($this->getUserId(), $hash, $gridName);

            if($dbResult === false) {
                throw new GeneralException('Could not save data to the database.');
            }

            $this->app->gridExportRepository->commit($this->getUserId(), __METHOD__);
        } catch(AException $e) {
            $this->app->gridExportRepository->rollback();

            throw new AjaxRequestException('Could not export grid.', $e);
        }

        $ge = new GridExporter($this->app->logger, $hash, $this->app->cfg, $this->app->serviceManager);
        $ge->setExportAll($exportAll);

        $count = $ge->getRowCount();
        if($count >= $this->app->cfg['MAX_GRID_EXPORT_SIZE'] && $exportAll) {
            $result = $ge->exportAsync();
        } else {
            $result = $ge->export();
        }

        if($result !== null) {
            if($result == 'async') {
                $updateData = [
                    'entryCount' => $count
                ];
            } else {
                $updateData = [
                    'filename' => str_replace('\\', '\\\\', $result),
                    'entryCount' => $ge->getEntryCount(),
                    'dateFinished' => DateTime::now()
                ];
            }
    
            try {
                $this->app->gridExportRepository->beginTransaction();
    
                $this->app->gridExportRepository->updateExportByHash($hash, $updateData);
    
                $this->app->gridExportRepository->commit($this->getUserId(), __METHOD__);
            } catch(AException $e) {
                $this->app->gridExportRepository->rollback();

                throw new AjaxRequestException('Could not export grid.', $e);
            }
        }

        $data = [];
        if($result === null) {
            $data = [
                'empty' => '1'
            ];
        } else if($result == 'async') {
            $data = [
                'empty' => 'async'
            ];
        } else {
            $data = [
                'empty' => '0',
                'path' => $result
            ];
        }

        return $data;
    }
}

?>