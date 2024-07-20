<?php

namespace App\Modules\ErrorModule;

use App\Components\Navbar\Navbar;
use App\Modules\AModule;

class ErrorModule extends AModule {
    public function __construct() {
        parent::__construct('ErrorModule');
    }

    public function renderModule() {
        global $app;

        $currentUserId = null;

        if($app->currentUser !== null) {
            $currentUserId = $app->currentUser->getId();
        }

        $navbar = new Navbar($app->notificationManager, $currentUserId);
        $navbar->hideSearchBar();
        
        if($app->currentUser == null) {
            $navbar->setCustomLinks(['topics' => ['page' => 'AnonymModule:Home'], 'login' => ['page' => 'AnonymModule:Login', 'action' => 'loginForm'], 'register' => ['page' => 'AnonymModule:Register', 'action' => 'form']]);
        }

        $this->template->sys_navbar = $navbar;
    }
}

?>