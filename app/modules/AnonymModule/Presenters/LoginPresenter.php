<?php

namespace App\Modules\AnonymModule;

use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class LoginPresenter extends APresenter {
    public function __construct() {
        parent::__construct('LoginPresenter', 'Login');
    }

    public function handleCheckLogin() {
        if(is_null($this->httpSessionGet('userId'))) {
            $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'loginForm']);
        } else {
            $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
        }
    }

    public function handleLoginForm() {
        global $app;

        if($this->httpGet('isSubmit') == 'true') {
            if($app->userAuth->loginUser($this->httpPost('username'), $this->httpPost('password'))) {
                $app->logger->info('Logged in user #' . $this->httpSessionGet('userId') . '.', __METHOD__);
                $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
            } else {
                $this->flashMessage('Bad credentials entered. Please try again.', 'error');
            }
        }
    }

    public function renderLoginForm() {
        $fb = new FormBuilder();
        
        $fb ->setAction(['page' => 'AnonymModule:Login', 'action' => 'loginForm', 'isSubmit' => 'true'])
            ->addTextInput('username', 'Username:')
            ->addPassword('password', 'Password:')
            ->addSubmit('Log in')
        ;

        $form = $fb->render();

        $this->template->form = $form;
        $this->template->title = 'Login';
    }
}

?>