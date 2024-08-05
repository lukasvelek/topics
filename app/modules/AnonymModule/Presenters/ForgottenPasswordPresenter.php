<?php

namespace App\Modules\AnonymModule;

use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use Exception;

class ForgottenPasswordPresenter extends AAnonymPresenter {
    public function __construct() {
        parent::__construct('ForgottenPasswordPresenter', 'Forgotten password');
    }

    public function handleForm(FormResponse $fr = null) {
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
}

?>