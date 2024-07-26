<?php

namespace App\Modules\UserModule;

use App\Components\Navbar\Navbar;
use App\Modules\AModule;

class UserModule extends AModule {
    public function __construct() {
        parent::__construct('UserModule');
    }

    public function renderModule() {
        global $app;
        
        $navbar = new Navbar($app->notificationManager, $app->currentUser->getId());
        if($this->template !== null) {
            $this->template->sys_navbar = $navbar;
        }
    }
}

?>