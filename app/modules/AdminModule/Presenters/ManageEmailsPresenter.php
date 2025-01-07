<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Helpers\GridHelper;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageEmailsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageEmailsPresenter', 'Email management');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageEmails($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function renderList() {
        $this->template->links = [];
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->mailRepository->composeQueryForEmailQueue(), 'mailId');
        $grid->setGridName(GridHelper::GRID_EMAIL_QUEUE);

        $grid->addColumnText('title', 'Title');
        $col = $grid->addColumnText('recipient', 'Recipient');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, HTML $html, mixed $value) {
            $user = $this->app->userRepository->getUserByEmail($value);

            if($user !== null) {
                return UserEntity::createUserProfileLink($user, false, 'grid-link');
            } else {
                return $value;
            }
        };
        $grid->addColumnDatetime('dateCreated', 'Date created');

        $sendNow = $grid->addAction('sendNow');
        $sendNow->setTitle('Send now');
        $sendNow->onCanRender[] = function() {
            return true;
        };
        $sendNow->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            return LinkBuilder::createSimpleLink('Send now', $this->createURL('sendNow', ['mailId' => $primaryKey]), 'grid-link');
        };

        return $grid;
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