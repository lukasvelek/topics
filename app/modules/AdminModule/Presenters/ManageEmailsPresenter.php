<?php

namespace App\Modules\AdminModule;

use App\Helpers\GridHelper;

class ManageEmailsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageEmailsPresenter', 'Email management');
    }

    public function startup() {
        parent::startup();

        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }
}

?>