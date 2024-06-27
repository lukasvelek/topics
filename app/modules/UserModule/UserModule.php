<?php

namespace App\Modules\UserModule;

use App\Components\Navbar\Navbar;
use App\Modules\AModule;
use App\Modules\TemplateObject;

class UserModule extends AModule {
    public function __construct() {
        parent::__construct('UserModule');
    }

    public function renderModule() {
        $navbar = new Navbar();
        if($this->template !== null) {
            $this->template->sys_navbar = $navbar->render();
        }
    }
}

?>