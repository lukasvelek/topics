<?php

namespace App\Modules\AnonymModule;

use App\Core\HashManager;
use App\Exceptions\AException;
use App\Exceptions\UserRegistrationException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;
use Exception;

class RegisterPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('RegisterPresenter', 'Register');
    }

    public function handleForm(?FormResponse $fr = null) {
        if($fr !== null) {
            $username = $fr->username;
            $password = HashManager::hashPassword($fr->password);
            $email = $fr->email;

            $error = 0;
            try {
                $this->app->userRepository->beginTransaction();

                if(!$this->app->userAuth->checkUser($username)) {
                    throw new UserRegistrationException('User with username \'' . $username . '\' already exists.');
                    $error = 1;
                }

                if(!$this->app->userAuth->checkUserByEmail($email)) {
                    throw new UserRegistrationException('User with email \'' . $email . '\' already exists.');
                    $error = 1;
                }

                $this->app->userRegistrationManager->registerUser($username, $password, $email);

                $this->app->userRepository->commit(null, __METHOD__);

                $this->flashMessage('You have been registered. Now you can log in.', 'success');
                $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
            } catch(AException|Exception $e) {
                $this->app->userRepository->rollback();
                
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
                ->addSubmit('Register', false, true)
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

    public function handleConfirm() {
        $registrationId = $this->httpGet('registrationId', true);

        try {
            $this->app->userRegistrationRepository->beginTransaction();

            $this->app->userRegistrationManager->confirmUserRegistration($registrationId);

            $this->app->userRegistrationRepository->commit(null, __METHOD__);

            $this->flashMessage('Your registration has been confirmed. You may now login.', 'success');
        } catch(AException|Exception $e) {
            $this->app->userRegistrationRepository->rollback();

            $link = LinkBuilder::createSimpleLink('here', $this->createURL('newConfirmationLink', ['oldRegistrationId' => $registrationId]), 'post-data-link');

            $this->flashMessage('Your registration could not be confirmed. Please click ' . $link . '  to obtain a new activation link.', 'error');
        }

        $this->redirect(['page' => 'AnonymModule:Home']);
    }

    public function handleNewConfirmationLink() {
        $oldRegistrationId = $this->httpGet('oldRegistrationId', true);

        try {
            $this->app->userRegistrationRepository->beginTransaction();

            $this->app->userRegistrationRepository->commit(null, __METHOD__);

            $this->flashMessage('New registration confirmation link has been sent.');
        } catch(AException|Exception $e) {
            $this->app->userRegistrationRepository->rollback();

            $text = 'New registration confirmation link could not be created. Please reach support';

            if($e instanceof AException) {
                $text .= ' with error ID: #' . $e->getHash();
            }

            $text .= ' for further assistance.';

            $this->flashMessage($text, 'error');
        }

        $this->redirect(['page' => 'AnonymModule:Home']);
    }
}

?>