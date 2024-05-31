<?php

namespace App\Modules\AnonymModule;

use App\Modules\APresenter;

class TestPresenter extends APresenter {
    public function __construct() {
        parent::__construct('TestPresenter', 'Test');
    }

    public function handleCheckLogin() {
    }

    public function renderCheckLogin() {
        
    }
}

?>