<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\BannedWordEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class ManageBannedWordsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageBannedWordsPresenter', 'Banned words management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });

        global $app;

        $this->gridHelper = new GridHelper($app->logger, $app->currentUser->getId());
    }

    public function actionGridList() {
        global $app;

        $gridPage = $this->httpGet('gridPage');
        $gridSize = $gridSize = $app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_BANNED_WORDS, $gridPage);

        $data = $app->contentRegulationRepository->getBannedWordsForGrid($gridSize, ($page * $gridSize));
        $totalCount = $app->contentRegulationRepository->getBannedWordsCount();
        $lastPage = ceil($totalCount / $gridSize);

        $gb = new GridBuilder();
        $gb->addColumns(['text' => 'Word', 'author' => 'Author', 'date' => 'Date']);
        $gb->addDataSource($data);
        $gb->addOnColumnRender('author', function(Cell $cell, BannedWordEntity $bwe) use ($app) {
            $user = $app->userRepository->getUserById($bwe->getAuthorId());
            return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], 'grid-link');
        });
        $gb->addOnColumnRender('date', function(Cell $cell, BannedWordEntity $bwe) {
            return DateTimeFormatHelper::formatDateToUserFriendly($bwe->getDateCreated());
        });
        $gb->addAction(function(BannedWordEntity $bwe) {
            return LinkBuilder::createSimpleLink('Delete', ['page' => 'AdminModule:ManageBannedWords', 'action' => 'delete', 'wordId' => $bwe->getId()], 'grid-link');
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getBannedWordsGrid');

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setMethod('GET')
            ->setURL(['page' => 'AdminModule:ManageBannedWords', 'action' => 'gridList'])
            ->updateHTMLElement('grid-content', 'grid')
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName('getBannedWordsGrid')
            ->setFunctionArguments(['_page']);

        $this->addScript($arb->build());
        $this->addScript('getBannedWordsGrid(-1)');

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

            try {
                $app->contentRegulationRepository->beginTransaction();

                $wordId = $app->entityManager->generateEntityId(EntityManager::BANNED_WORDS);

                $app->contentRegulationRepository->createNewBannedWord($wordId, $word, $app->currentUser->getId());

                $app->contentRegulationRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Word banned.', 'success');
            } catch(AException $e) {
                $app->contentRegulationRepository->rollback();

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
        global $app;

        $wordId = $this->httpGet('wordId', true);

        try {
            $app->contentRegulationRepository->beginTransaction();

            $app->contentRegulationRepository->deleteBannedWord($wordId);

            $app->contentRegulationRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Word unbanned.', 'success');
        } catch(AException $e) {
            $app->contentRegulationRepository->rollback();

            $this->flashMessage('Could not unban word. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'AdminModule:ManageBannedWords', 'action' => 'list']);
    }
}

?>