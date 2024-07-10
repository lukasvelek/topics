<?php

namespace App\Modules\ErrorModule;

use App\Modules\APresenter;

abstract class AErrorPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'ErrorModule';
    }
}

?>