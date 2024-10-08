<?php

namespace App\Modules\AnonymModule;

use App\Core\HashManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;
use Exception;

class ForgottenPasswordPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('ForgottenPasswordPresenter', 'Forgotten password');
    }

    public function handleForm(?FormResponse $fr = null) {
        if($this->httpGet('isFormSubmit') == '1') {
            $username = $fr->username;

            try {
                $this->app->userRepository->beginTransaction();

                $user = $this->app->userManager->getUserByUsername($username);

                if($user === null) {
                    throw new GeneralException('User with this username does not exist.');
                }

                $this->app->userManager->createNewForgottenPassword($user->getId());

                $this->app->userRepository->commit(null, __METHOD__);
            } catch(AException|Exception $e) {
                $this->app->userRepository->rollback();
            }

            $this->flashMessage('Email with instructions to reset your password sent.');
            $this->redirect();
        } else {
            $fb = new FormBuilder();
    
            $fb ->setAction($this->createURL('form'))
                ->addTextInput('username', 'Username:', null, true)
                ->addSubmit('Submit', false, true);
    
            $this->saveToPresenterCache('form', $fb);

            $link = LinkBuilder::createSimpleLink('Login', ['page' => 'AnonymModule:Login', 'action' => 'loginForm'], 'post-data-link');

            $this->saveToPresenterCache('link', $link);
        }
    }

    public function renderForm() {
        $form = $this->loadFromPresenterCache('form');
        $link = $this->loadFromPresenterCache('link');

        $this->template->form = $form;
        $this->template->link = $link;
    }

    public function handleChangePasswordForm(?FormResponse $fr = null) {
        $linkId = $this->httpGet('linkId');

        if(!$this->app->userManager->checkForgottenPasswordRequest($linkId)) {
            $this->flashMessage('This request has expired. Please request again.', 'error');
            $this->redirect(['page' => 'AnonymModule:Home']);
        }

        if($this->httpGet('isFormSubmit') == '1') {
            $password = HashManager::hashPassword($fr->password);

            try {
                $this->app->userRepository->beginTransaction();

                $this->app->userManager->processForgottenPasswordRequestPasswordChange($linkId, $password);

                $this->app->userRepository->commit(null, __METHOD__);

                $this->flashMessage('Password updated. You may now log in.', 'success');
            } catch(AException|Exception $e) {
                $this->app->userRepository->rollback();

                $this->flashMessage('Could not change password. Please try again.', 'error');
            }

            $this->redirect(['page' => 'AnonymModule:Home']);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction($this->createURL('changePasswordForm', ['linkId' => $linkId]))
                ->addPassword('password', 'Password:', null, true)
                ->addPassword('passwordCheck', 'Password again:', null, true)
                ->addSubmit('Save', false, true)
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