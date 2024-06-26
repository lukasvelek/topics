<?php

namespace App\Modules\AdminModule;

use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;

class ManageBannedWordsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageBannedWordsPresenter', 'Banned words management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function handleList() {
        global $app;

        $gridScript = '<script type="text/javascript">getBannedWords(0, ' . $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('grid', $gridScript);

        $links = [
            '<a class="post-data-link" href="?page=AdminModule:ManageBannedWords&action=newForm">Add word</a>'
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $grid = $this->loadFromPresenterCache('grid');
        $links = $this->loadFromPresenterCache('links');

        $this->template->grid_script = $grid;
        $this->template->links = $links;
    }

    public function handleNewForm(?FormResponse $fr = null) {
        global $app;

        if($this->httpGet('isSubmit') == '1') {
            $word = $fr->word;

            $app->contentRegulationRepository->createNewBannedWord($word, $app->currentUser->getId());

            $this->flashMessage('Word banned.', 'success');
            $this->redirect(['page' => 'AdminModule:ManageBannedWords', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'AdminModule:ManageBannedWords', 'action' => 'newForm', 'isSubmit' => '1'])
                ->addTextInput('word', 'Word to ban:', null, true)
                ->addSubmit('Add')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderNewForm() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handleDelete() {
        global $app;

        $wordId = $this->httpGet('wordId', true);

        $app->contentRegulationRepository->deleteBannedWord($wordId);

        $this->flashMessage('Word unbanned.', 'success');
        $this->redirect(['page' => 'AdminModule:ManageBannedWords', 'action' => 'list']);
    }
}

?>