<?php

namespace App\Modules\AnonymModule;

use App\Components\Navbar\Navbar;
use App\Modules\AModule;

class AnonymModule extends AModule {
    public function __construct() {
        parent::__construct('AnonymModule');
    }

    public function renderModule() {
        $navbar = new Navbar($this->app->notificationManager);
        $navbar->hideSearchBar();
        $navbar->setIsCurrentUserIsAdmin($this->app->currentUser?->isAdmin());
        
        if($this->app->currentUser === null) {
            $navbar->setCustomLinks(['topics' => ['page' => 'AnonymModule:Home'], 'login' => ['page' => 'AnonymModule:Login', 'action' => 'loginForm'], 'register' => ['page' => 'AnonymModule:Register', 'action' => 'form']]);
        }

        $this->template->sys_navbar = $navbar;
    }
}

?>