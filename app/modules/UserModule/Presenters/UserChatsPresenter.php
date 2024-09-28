<?php

namespace App\Modules\UserModule;

class UserChatsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('UserChatsPresenter', 'Chats');
    }

    public function startup() {
        parent::startup();
    }
}

?>