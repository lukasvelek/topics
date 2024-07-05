<?php

namespace App\Modules\AnonymModule;

use App\Modules\APresenter;

class AboutPresenter extends APresenter {
    public function __construct() {
        parent::__construct('AboutPresenter', 'About');
        $this->setStatic();
    }

    public function renderChangelog() {}
}

?>