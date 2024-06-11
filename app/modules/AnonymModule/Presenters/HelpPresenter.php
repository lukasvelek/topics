<?php

namespace App\Modules\AnonymModule;

use App\Constants\SuggestionCategory;
use App\Exceptions\AException;
use App\Exceptions\RequiredAttributeIsNotSetException;
use App\Modules\APresenter;
use App\UI\FormBuilder\ElementDuo;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\Label;
use App\UI\FormBuilder\Select;

class HelpPresenter extends APresenter {
    public function __construct() {
        parent::__construct('HelpPresenter', 'Help');
    }

    public function handleForm() {
        global $app;

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $title = $this->httpPost('title');
            $text = $this->httpPost('text');
            $category = $this->httpPost('category');
            $user = $this->httpPost('user');

            try {
                $app->suggestionRepository->createNewSuggestion($user, $title, $text, $category);
            } catch(AException $e) {
                $app->flashMessage('Could not create a suggestion. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
            }

            $app->flashMessage('Suggestion created. Thank you :)', 'success');
            $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
        } else {
            try {
                $this->httpGet('userId', true);
            } catch(AException $e) {
                $this->flashMessage('No user specified. Please try again.', 'error');
                $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
            }
            
            $user = $app->currentUser;

            $categoryOptions = SuggestionCategory::createSelectOptionArray();
            $userOptions = [
                [
                    'value' => $user->getId(),
                    'text' => $user->getUsername(),
                    'selected' => 'selected'
                ]
            ];
    
            $userSelect = new Select('user', $userOptions);
            $userLabel = new Label('User:', 'user', true);
            $userDuo = new ElementDuo($userSelect, $userLabel);
            
            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'AnonymModule:Help', 'action' => 'form', 'isSubmit' => '1'])
                ->addElement('user', $userDuo)
                ->addTextInput('title', 'Title:', null, true)
                ->addTextArea('text', 'Text:', null, true)
                ->addSelect('category', 'Category:', $categoryOptions)
                ->addSubmit('Send suggestion')
            ;
    
            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderForm() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }
}

?>