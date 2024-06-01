<?php

namespace App\Modules\AnonymModule;

use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class LoginPresenter extends APresenter {
    public function __construct() {
        parent::__construct('LoginPresenter', 'Login');
    }

    public function handleCheckLogin() {
        global $app;

        if(isset($_COOKIE['userId'])) {
            $app->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
        } else {
            $app->redirect(['page' => 'AnonymModule:Login', 'action' => 'loginForm']);
        }
    }

    public function renderLoginForm() {
        $fb = new FormBuilder();
        
        $fb ->setAction(['page' => 'AnonymModule:Login', 'action' => 'loginForm', 'isSubmit' => 'true'])
            ->addTextInput('username', 'Username:')
            ->addSelect('gender', 'Gender:', [['value' => 'male', 'text' => 'Male', 'selected' => null], ['value' => 'female', 'text' => 'Female']])
            ->addPassword('password', 'Password:')
            ->addSubmit()
        ;

        $form = $fb->render();

        $this->template->form = $form;
    }
}

?>