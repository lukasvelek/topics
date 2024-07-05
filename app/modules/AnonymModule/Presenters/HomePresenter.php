<?php

namespace App\Modules\AnonymModule;

use App\Modules\APresenter;

class HomePresenter extends APresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Topics');

        $this->setDefaultAction('default');
    }

    public function handleDefault() {}

    public function renderDefault() {}
}

?>