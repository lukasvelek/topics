<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\EmailEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\GridBuilder\Cell;
use App\UI\LinkBuilder;

class ManageEmailsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageEmailsPresenter', 'Email management');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageEmails($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }

        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setURL($this->createURL('getGrid'))
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName('getGrid')
            ->setFunctionArguments(['_page'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb);
        $this->addScript('getGrid(0)');

        $links = [];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetGrid() {
        $grid = $this->getGridBuilder();

        $gridPage = $this->httpGet('gridPage');
        $gridSize = $this->app->getGridSize();
        
        $page = $this->gridHelper->getGridPage(GridHelper::GRID_EMAIL_QUEUE, $gridPage);

        $emails = $this->app->mailRepository->getAllEntriesLimited($gridSize, ($gridSize * $page));
        $totalCount = count($this->app->mailRepository->getAllEntriesLimited(0, 0));
        $lastPage = ceil($totalCount / $gridSize);

        $grid->addDataSource($emails);
        $grid->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getGrid');
        $grid->addColumns(['title' => 'Title', 'recipient' => 'Recipient', 'dateCreated' => 'Date created']);

        $grid->addOnColumnRender('recipient', function(Cell $cell, EmailEntity $email) {
            $user = $this->app->userRepository->getUserByEmail($email->getRecipient());

            return UserEntity::createUserProfileLink($user, false, 'grid-link');
        });
        $grid->addOnColumnRender('dateCreated', function(Cell $cell, EmailEntity $ee) {
            $cell->setValue(DateTimeFormatHelper::formatDateToUserFriendly($ee->getDateCreated()));
            $cell->setTitle(DateTimeFormatHelper::formatDateToUserFriendly($ee->getDateCreated(), DateTimeFormatHelper::ATOM_FORMAT));
            return $cell;
        });

        $grid->addAction(function(EmailEntity $email) {
            return LinkBuilder::createSimpleLink('Send now', $this->createURL('sendNow', ['mailId' => $email->getId()]), 'grid-link');
        });

        return ['grid' => $grid->build()];
    }

    public function handleSendNow() {
        $mailId = $this->httpGet('mailId', true);

        try {
            $mail = $this->app->mailRepository->getEntityById($mailId);

            $this->app->mailManager->sendEmail($mail);

            $this->app->mailRepository->beginTransaction();

            $this->app->mailManager->deleteEmailEntry($mailId);

            $this->app->mailRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('Email sent.', 'success');
        } catch(AException $e) {
            $this->app->mailRepository->rollback();

            $this->flashMessage('Could not send email. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('list'));
    }
}

?>