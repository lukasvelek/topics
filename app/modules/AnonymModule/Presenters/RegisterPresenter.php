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
            $password = HashManager::hashPassword($fr->password);
            $email = $fr->email;

            $error = 0;
            try {
                $app->userRepository->beginTransaction();

                if(!$app->userAuth->checkUser($username)) {
                    throw new UserRegistrationException('User with username \'' . $username . '\' already exists.');
                    $error = 1;
                }

                if(!$app->userAuth->checkUserByEmail($email)) {
                    throw new UserRegistrationException('User with email \'' . $email . '\' already exists.');
                    $error = 1;
                }

                $app->userRegistrationManager->registerUser($username, $password, $email);

                $app->userRepository->commit(null, __METHOD__);

                $this->flashMessage('You have been registered. Now you can log in.', 'success');
                $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
            } catch(AException|Exception $e) {
                $app->userRepository->rollback();
                
                if($error == 0) {
                    $this->flashMessage('User with these credentials exists.', 'error');
                } else {
                    if($e instanceof AException) {
                        $this->flashMessage('Your account could not be created. Please try again or contact support with this error ID: #' . $e->getHash() . ' for further assistance.', 'error');
                    } else {
                        $this->flashMessage('Your account could not be created. Please try again or contact support for further assistance.', 'error');
                    }
                }
                
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