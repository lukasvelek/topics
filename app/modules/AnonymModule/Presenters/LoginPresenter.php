<?php

namespace App\Modules\AnonymModule;

use App\Exceptions\AException;
use App\Exceptions\DatabaseExecutionException;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;

class LoginPresenter extends APresenter {
    public function __construct() {
        parent::__construct('LoginPresenter', 'Login');
    }

    public function handleCheckLogin() {
        if(is_null($this->httpSessionGet('userId'))) {
            $this->redirect(['page' => 'AnonymModule:Home']);
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
            } catch (DatabaseExecutionException $e) {
                $this->flashMessage('Could not log in due to internal error. [' . $e->getHash() . ']', 'error');
                $this->redirect();
            } catch (AException $e) {
                $this->flashMessage($e->getMessage(), 'error');
                $this->redirect();
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