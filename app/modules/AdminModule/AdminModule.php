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
        global $app;
        $navbar = new Navbar($app->notificationManager, $app->currentUser->getId());
        $navbar->setCustomLinks(NavbarAdminLinks::toArray());
        $navbar->hideSearchBar();
        $this->template->sys_navbar = $navbar;
    }
}

?>