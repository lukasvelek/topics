<?php

namespace App\Modules\UserModule;

use App\Modules\APresenter;

class LogoutPresenter extends APresenter {
    public function __construct() {
        parent::__construct('LogoutPresenter', 'Logout');
    }

    public function handleLogout() {
        session_unset();

        $_SESSION['is_logging_in'] = true;

        $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'loginForm']);
    }
}

?>