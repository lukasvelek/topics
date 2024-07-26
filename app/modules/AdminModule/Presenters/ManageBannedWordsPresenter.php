<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\BannedWordEntity;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class ManageBannedWordsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageBannedWordsPresenter', 'Banned words management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function actionGridList() {
        global $app;

        $page = $this->httpGet('gridPage');

        $gridSize = $gridSize = $app->getGridSize();

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
        $this->addScript('getBannedWordsGrid(0)');

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

        $app->contentRegulationRepository->deleteBannedWord($wordId);

        $this->flashMessage('Word unbanned.', 'success');
        $this->redirect(['page' => 'AdminModule:ManageBannedWords', 'action' => 'list']);
    }
}

?>