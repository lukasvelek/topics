<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Helpers\GridHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageBannedWordsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageBannedWordsPresenter', 'Banned words management');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageBannedWords($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->contentRegulationRepository->composeQueryForBannedWords(), 'wordId');
        $grid->setGridName(GridHelper::GRID_BANNED_WORDS);

        $grid->addColumnText('word', 'Word');
        $grid->addColumnUser('authorId', 'Author');
        $grid->addColumnDatetime('dateCreated', 'Date');

        $delete = $grid->addAction('delete');
        $delete->setTitle('Delete');
        $delete->onCanRender[] = function() {
            return true;
        };
        $delete->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            return LinkBuilder::createSimpleLink('Delete', $this->createURL('delete', ['wordId' => $primaryKey]), 'grid-link');
        };

        return $grid;
    }

    public function handleList() {
        $links = [
            LinkBuilder::createSimpleLink('Add word', $this->createURL('newForm'), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function handleNewForm(?FormResponse $fr = null) {
        if($this->httpGet('isSubmit') == '1') {
            $word = $fr->word;

            try {
                $this->app->contentRegulationRepository->beginTransaction();

                $wordId = $this->app->entityManager->generateEntityId(EntityManager::BANNED_WORDS);

                $this->app->contentRegulationRepository->createNewBannedWord($wordId, $word, $this->getUserId());

                $this->app->contentRegulationRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('Word banned.', 'success');
            } catch(AException $e) {
                $this->app->contentRegulationRepository->rollback();

                $this->flashMessage('Could not ban word. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'AdminModule:ManageBannedWords', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'AdminModule:ManageBannedWords', 'action' => 'newForm', 'isSubmit' => '1'])
                ->addTextInput('word', 'Word to ban:', null, true)
                ->addSubmit('Add', false, true)
            ;

            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderNewForm() {
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->links = $links;
    }

    public function handleDelete() {
        $wordId = $this->httpGet('wordId', true);

        try {
            $this->app->contentRegulationRepository->beginTransaction();

            $this->app->contentRegulationRepository->deleteBannedWord($wordId);

            $this->app->contentRegulationRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Word unbanned.', 'success');
        } catch(AException $e) {
            $this->app->contentRegulationRepository->rollback();

            $this->flashMessage('Could not unban word. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'AdminModule:ManageBannedWords', 'action' => 'list']);
    }
}

?>