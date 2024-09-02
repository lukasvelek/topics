<?php

namespace App\Modules\UserModule;

use App\UI\GridBuilder\GridExporter;

class GridExportHelperPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('GridExportHelperPresenter', 'Grid Export Helper');
    }

    public function actionExportGrid() {
        global $app;
        
        $hash = $this->httpGet('hash', true);

        $ge = new GridExporter($app->logger, $hash, $app->cfg);
        $result = $ge->export();

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