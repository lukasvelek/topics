<?php

namespace App\Modules\AdminModule;

use App\Components\Navbar\Navbar;
use App\Components\Navbar\NavbarAdminLinks;
use App\Modules\AModule;

class AdminModule extends AModule {
    public function __construct() {
        parent::__construct('AdminModule');
    }

    public function renderModule() {
        $navbar = new Navbar($this->app->notificationManager, $this->app->systemStatusManager, $this->app->currentUser->getId());
        $navbar->setCustomLinks(NavbarAdminLinks::toArray());
        $navbar->hideSearchBar();
        $navbar->setIsCurrentUserIsAdmin($this->app->currentUser?->isAdmin());
        $this->template->sys_navbar = $navbar;
    }
}

?>