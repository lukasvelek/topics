<?php

namespace App\Modules\ErrorModule;

use App\Modules\APresenter;

class E500Presenter extends APresenter {
    public function __construct() {
        parent::__construct('E500Presenter', 'Error 500');

        $this->setDefaultAction('default');
    }

    public function handleDefault() {
        $text = 'Internal server error';

        $this->saveToPresenterCache('text', $text);
    }

    public function renderDefault() {
        $text = $this->loadFromPresenterCache('text');

        $this->template->content = $text;
    }
}

?>