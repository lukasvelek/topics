<?php

namespace App\Modules\AnonymModule;

use App\Exceptions\AException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;

class LoginPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('LoginPresenter', 'Login');
    }

    public function handleCheckLogin() {
        $fm = $this->httpGet('_fm');

        if(is_null($this->httpSessionGet('userId'))) {
            $url = ['page' => 'AnonymModule:Home'];
        } else {
            $url = ['page' => 'UserModule:Home', 'action' => 'dashboard'];
        }

        if($fm !== null) {
            $url['_fm'] = $fm;
        }

        $this->redirect($url);
    }

    public function handleLoginForm(?FormResponse $fr = null) {
        if($this->httpGet('isSubmit') == 'true') {
            try {
                $this->app->userAuth->loginUser($fr->username, $fr->password);
                
                $this->app->logger->info('Logged in user #' . $this->httpSessionGet('userId') . '.', __METHOD__);
                $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
            } catch(AException $e) {
                $this->flashMessage('Could not log in due to internal error. Reason: ' . $e->getMessage(), 'error', 15);
                $this->redirect($this->createURL('loginForm'));
            }
        } else {
            $fb = new FormBuilder();
        
            $fb ->setAction(['page' => 'AnonymModule:Login', 'action' => 'loginForm', 'isSubmit' => 'true'])
                ->addTextInput('username', 'Username:', null, true)
                ->addPassword('password', 'Password:', null, true)
                ->addSubmit('Log in')
            ;

            $this->saveToPresenterCache('form', $fb);

            $forgottenPasswordLink = LinkBuilder::createSimpleLink('Forgotten password', ['page' => 'AnonymModule:ForgottenPassword', 'action' => 'form'], 'post-data-link');

            $this->saveToPresenterCache('forgottenPasswordLink', $forgottenPasswordLink);
        }
    }

    public function renderLoginForm() {
        $form = $this->loadFromPresenterCache('form');
        $forgottenPasswordLink = $this->loadFromPresenterCache('forgottenPasswordLink');

        $this->template->form = $form;
        $this->template->title = 'Login';
        $this->template->forgotten_password_link = $forgottenPasswordLink;
    }
}

?>