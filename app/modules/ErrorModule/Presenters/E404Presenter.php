<?php

namespace App\Modules\ErrorModule;

use App\Modules\APresenter;

class E404Presenter extends APresenter {
    public function __construct() {
        parent::__construct('E404Presenter', 'Error 404');

        $this->setDefaultAction('default');
    }

    public function handleDefault() {
        $reason = $this->httpGet('reason');

        if($reason == 'ActionDoesNotExist') {
            $text = 'This action does not exist.';
        } else {
            $text = 'This page does not exist.';
        }

        $this->saveToPresenterCache('text', $text);
    }

    public function renderDefault() {
        $text = $this->loadFromPresenterCache('text');

        $this->template->content = $text;
    }
}

?>