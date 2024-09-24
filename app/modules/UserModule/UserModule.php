<?php

namespace App\Modules\UserModule;

use App\Components\Navbar\Navbar;
use App\Modules\AModule;

class UserModule extends AModule {
    public function __construct() {
        parent::__construct('UserModule');
    }

    public function renderModule() {
        $navbar = new Navbar($this->app->notificationManager, $this->app->currentUser->getId());
        $navbar->setIsCurrentUserIsAdmin($this->app->currentUser?->isAdmin());
        if($this->template !== null) {
            $this->template->sys_navbar = $navbar;
        }
    }
}

?>