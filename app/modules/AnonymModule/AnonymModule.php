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

        $navbar = new Navbar($app->notificationManager);
        $navbar->hideSearchBar();
        
        if($app->currentUser == null) {
            $navbar->setCustomLinks(['topics' => ['page' => 'AnonymModule:Home'], 'login' => ['page' => 'AnonymModule:Login', 'action' => 'loginForm'], 'register' => ['page' => 'AnonymModule:Register', 'action' => 'form']]);
        }

        $this->template->sys_navbar = $navbar;
    }
}

?>