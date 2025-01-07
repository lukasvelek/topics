<?php

namespace App\Modules\AnonymModule;

class AboutPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('AboutPresenter', 'About');
    }

    public function renderChangelog() {}
}

?>