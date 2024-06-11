<?php

namespace App\Modules\AdminModule;

use App\Modules\APresenter;

class HomePresenter extends APresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {

    }
}

?>