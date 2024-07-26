<?php

namespace App\Modules\AnonymModule;

use App\Core\HashManager;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;

class RegisterPresenter extends APresenter {
    public function __construct() {
        parent::__construct('RegisterPresenter', 'Register');
    }

    public function handleForm(?FormResponse $fr = null) {
        global $app;

        if($fr !== null) {
            $username = $fr->username;
            $password = $fr->password;
            $email = $fr->email;

            if(!$app->userAuth->checkUser($username)) {
                $this->flashMessage('User with these credentials already exists. Please choose different credentials.', 'error');
                $this->logger->error('User with usernane "' . $username . '" already exists.', __METHOD__);
                $this->redirect();
            }

            if(!$app->userAuth->checkUserByEmail($email)) {
                $this->flashMessage('User with this email already exists. Please choose different email.', 'error');
                $this->logger->error('User with email "' . $email . '" already exists.', __METHOD__);
                $this->redirect();
            }

            if($app->userRepository->createNewUser($username, HashManager::hashPassword($password), $email, false)) {
                $this->flashMessage('You have been registered. Now you can log in.', 'success');
                $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
            } else {
                $this->flashMessage('Could not create a user. Please try again later.', 'error');
                $this->redirect();
            }
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AnonymModule:Register', 'action' => 'form'])
                ->addTextInput('username', 'Username:', null, true)
                ->addPassword('password', 'Password:', null, true)
                ->addPassword('passwordCheck', 'Password again:', null, true)
                ->addEmailInput('email', 'Email:', null, true)
                ->addSubmit('Register')
                ->addJSHandler('js/UserRegistrationFormHandler.js')
            ;

            $this->saveToPresenterCache('form', $fb);
        }

        $this->saveToPresenterCache('title', 'Registration form');
    }

    public function renderForm() {
        $title = $this->loadFromPresenterCache('title');
        $form = $this->loadFromPresenterCache('form');

        $this->template->title = $title;
        $this->template->form = $form;
    }
}

?>