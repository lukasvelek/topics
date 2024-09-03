<?php

namespace App\Modules\UserModule;

use App\Core\Datetypes\DateTime;
use App\Exceptions\AException;
use App\UI\GridBuilder\GridExporter;

class GridExportHelperPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('GridExportHelperPresenter', 'Grid Export Helper');
    }

    public function actionExportGrid() {
        global $app;
        
        $hash = $this->httpGet('hash', true);
        $exportAll = $this->httpGet('exportAll', true);
        $gridName = $this->httpGet('gridName', true);

        $exportAll = ($exportAll == 'true');

        try {
            $app->gridExportRepository->beginTransaction();

            $app->gridExportRepository->createNewExport($app->currentUser->getId(), $hash, $gridName);

            $app->gridExportRepository->commit($app->currentUser->getId(), __METHOD__);
        } catch(AException $e) {
            $app->gridExportRepository->rollback();

            $this->ajaxSendResponse(['empty' => '1']);

            return;
        }


        $ge = new GridExporter($app->logger, $hash, $app->cfg);
        $ge->setExportAll($exportAll);
        $result = $ge->export();

        if($result !== null) {
            $updateData = [
                'filename' => str_replace('\\', '\\\\', $result),
                'entryCount' => $ge->getEntryCount(),
                'dateFinished' => DateTime::now()
            ];
    
            try {
                $app->gridExportRepository->beginTransaction();
    
                $app->gridExportRepository->updateExportByHash($hash, $updateData);
    
                $app->gridExportRepository->commit($app->currentUser->getId(), __METHOD__);
            } catch(AException $e) {
                $app->gridExportRepository->rollback();
            }
        }

        $data = [];
        if($result === null) {
            $data = [
                'empty' => '1'
            ];
        } else {
            $data = [
                'empty' => '0',
                'path' => $result
            ];
        }

        $this->ajaxSendResponse($data);
    }
}

?>