<?php

namespace App\Modules\AnonymModule;

use App\Exceptions\AException;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;

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

    public function handleLoginForm(?FormResponse $fr = null) {
        global $app;

        if($this->httpGet('isSubmit') == 'true') {
            try {
                $app->userAuth->loginUser($fr->username, $fr->password);
                
                $app->logger->info('Logged in user #' . $this->httpSessionGet('userId') . '.', __METHOD__);
                $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
            } catch (AException $e) {
                $this->flashMessage($e->getMessage(), 'error');
                $this->redirect();
            }
        }
    }

    public function renderLoginForm() {
        $fb = new FormBuilder();
        
        $fb ->setAction(['page' => 'AnonymModule:Login', 'action' => 'loginForm', 'isSubmit' => 'true'])
            ->addTextInput('username', 'Username:', null, true)
            ->addPassword('password', 'Password:', null, true)
            ->addSubmit('Log in')
        ;

        $this->template->form = $fb;
        $this->template->title = 'Login';
    }
}

?>