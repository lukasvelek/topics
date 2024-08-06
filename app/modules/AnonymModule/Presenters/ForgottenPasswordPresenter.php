<?php

namespace App\Modules\AnonymModule;

use App\Core\HashManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use Exception;

class ForgottenPasswordPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('ForgottenPasswordPresenter', 'Forgotten password');
    }

    public function handleForm(?FormResponse $fr = null) {
        global $app;

        if($this->httpGet('isFormSubmit') == '1') {
            $username = $fr->username;

            try {
                $app->userRepository->beginTransaction();

                $user = $app->userManager->getUserByUsername($username);

                if($user === null) {
                    throw new GeneralException('User with this username does not exist.');
                }

                $app->userManager->createNewForgottenPassword($user->getId());

                $app->userRepository->commit(null, __METHOD__);
            } catch(AException|Exception $e) {
                $app->userRepository->rollback();
            }

            $this->flashMessage('Email with instructions to reset your password sent.');
            $this->redirect();
        } else {
            $fb = new FormBuilder();
    
            $fb ->setAction($this->createURL('form'))
                ->addTextInput('username', 'Username:', null, true)
                ->addSubmit('Submit');
    
            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderForm() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleChangePasswordForm(?FormResponse $fr = null) {
        global $app;

        $linkId = $this->httpGet('linkId');

        if(!$app->userManager->checkForgottenPasswordRequest($linkId)) {
            $this->flashMessage('This request has expired. Please request again.', 'error');
            $this->redirect(['page' => 'AnonymModule:Home']);
        }

        if($this->httpGet('isFormSubmit') == '1') {
            $password = HashManager::hashPassword($fr->password);

            try {
                $app->userRepository->beginTransaction();

                $app->userManager->processForgottenPasswordRequestPasswordChange($linkId, $password);

                $app->userRepository->commit(null, __METHOD__);

                $this->flashMessage('Password updated. You may now log in.', 'success');
            } catch(AException|Exception $e) {
                $app->userRepository->rollback();

                $this->flashMessage('Could not change password. Please try again.', 'error');
            }

            $this->redirect(['page' => 'AnonymModule:Home']);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction($this->createURL('changePasswordForm', ['linkId' => $linkId]))
                ->addPassword('password', 'Password:', null, true)
                ->addPassword('passwordCheck', 'Password again:', null, true)
                ->addSubmit('Save')
                ->addJSHandler('js/UserRegistrationFormHandler.js')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }
    
    public function renderChangePasswordForm() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }
}

?>