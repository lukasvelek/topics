<?php

namespace App\Modules\AnonymModule;

use App\Components\Navbar\Navbar;
use App\Modules\AModule;

class AnonymModule extends AModule {
    public function __construct() {
        parent::__construct('AnonymModule');
    }

    public function renderModule() {
        global $app;

        $navbar = new Navbar();
        $navbar->hideSearchBar();
        
        if($app->currentUser == null) {
            $navbar->setCustomLinks(['login' => ['page' => 'AnonymModule:Login', 'action' => 'loginForm']]);
        }

        $this->template->sys_navbar = $navbar->render();
    }
}

?>