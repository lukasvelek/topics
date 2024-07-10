<?php

namespace App\Modules\UserModule;

use App\Modules\APresenter;

abstract class AUserPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'UserModule';
    }
}

?>