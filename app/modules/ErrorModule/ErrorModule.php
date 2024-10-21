<?php

namespace App\Modules\ErrorModule;

use App\Components\Navbar\Navbar;
use App\Modules\AModule;

class ErrorModule extends AModule {
    public function __construct() {
        parent::__construct('ErrorModule');
    }

    public function renderModule() {
        $currentUserId = null;

        if($this->app->currentUser !== null) {
            $currentUserId = $this->app->currentUser->getId();
        }

        $navbar = new Navbar($this->app->notificationManager, $this->app->systemStatusManager, $currentUserId);
        $navbar->hideSearchBar();
        $navbar->setIsCurrentUserIsAdmin($this->app->currentUser?->isAdmin());
        
        if($this->app->currentUser === null) {
            $navbar->setCustomLinks(['topics' => ['page' => 'AnonymModule:Home'], 'login' => ['page' => 'AnonymModule:Login', 'action' => 'loginForm'], 'register' => ['page' => 'AnonymModule:Register', 'action' => 'form']]);
        }

        $this->template->sys_navbar = $navbar;
    }
}

?>