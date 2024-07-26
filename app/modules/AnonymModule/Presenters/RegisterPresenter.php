<?php

namespace App\Modules\AnonymModule;

use App\Core\HashManager;
use App\Exceptions\AException;
use App\Exceptions\UserRegistrationException;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use Exception;

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

            try {
                $app->userRepository->beginTransaction();

                if(!$app->userAuth->checkUser($username)) {
                    throw new UserRegistrationException('User with username \'' . $username . '\' already exists.');
                }

                if(!$app->userAuth->checkUserByEmail($email)) {
                    throw new UserRegistrationException('User with email \'' . $email . '\' already exists.');
                }

                $app->userRepository->createNewUser($username, HashManager::hashPassword($password), $email, false);

                $app->userRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('You have been registered. Now you can log in.', 'success');
                $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
            } catch(AException|Exception $e) {
                $app->userRepository->rollback();
                
                $this->flashMessage('Your registration could not be finished. Reason: ' . $e->getMessage(), 'error');
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