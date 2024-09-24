<?php

namespace App\Modules\AnonymModule;

use App\Constants\SuggestionCategory;
use App\Exceptions\AException;
use App\Managers\EntityManager;
use App\Modules\APresenter;
use App\UI\FormBuilder\ElementDuo;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\FormBuilder\Label;
use App\UI\FormBuilder\Select;

class HelpPresenter extends APresenter {
    public function __construct() {
        parent::__construct('HelpPresenter', 'Help');
    }

    public function handleForm(?FormResponse $fr = null) {
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $title = $fr->title;
            $text = $fr->text;
            $category = $fr->category;
            $user = $fr->user;

            $fmHash = '';

            try {
                $this->app->suggestionRepository->beginTransaction();

                $suggestionId = $this->app->entityManager->generateEntityId(EntityManager::SUGGESTIONS);

                $this->app->suggestionRepository->createNewSuggestion($suggestionId, $user, $title, $text, $category);

                $this->app->suggestionRepository->commit($this->getUserId(), __METHOD__);
                
                $fmHash = $this->app->flashMessage('Suggestion created. Thank you :)', 'success');
            } catch(AException $e) {
                $this->app->suggestionRepository->rollback();

                $fmHash = $this->app->flashMessage('Could not create a suggestion. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin', '_fm' => $fmHash]);
        } else {
            try {
                $this->httpGet('userId', true);
            } catch(AException $e) {
                $this->flashMessage('No user specified. Please try again.', 'error');
                $this->redirect(['page' => 'AnonymModule:Login', 'action' => 'checkLogin']);
            }
            
            $user = $this->getUser();

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
            $userDuo = new ElementDuo($userSelect, $userLabel, 'user');
            
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

        $this->template->form = $form;
    }
}

?>