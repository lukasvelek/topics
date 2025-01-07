<?php

namespace App\Modules\AnonymModule;

class HomePresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Topics');

        $this->setDefaultAction('default');
    }

    public function handleDefault() {}

    public function renderDefault() {}
}

?>