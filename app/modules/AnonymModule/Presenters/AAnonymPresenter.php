<?php

namespace App\Modules\AnonymModule;

use App\Modules\APresenter;

abstract class AAnonymPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'AnonymModule';
    }
}

?>