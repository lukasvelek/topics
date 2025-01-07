<?php

namespace App\Modules\AdminModule;

use App\Constants\UserProsecutionType;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Helpers\GridHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageUserProsecutionsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageUserProsecutionsPresenter', 'Manage user prosecutions');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageUserProsecutions($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->userProsecutionRepository->composeQueryForProsecutions(), 'prosecutionId');
        $grid->setGridName(GridHelper::GRID_USER_PROSECUTIONS);
        
        $grid->addColumnUser('userId', 'User');
        $grid->addColumnText('type', 'Type');
        $grid->addColumnDatetime('startDate', 'Date from');
        $grid->addColumnDatetime('endDate', 'Date to');

        $removeBan = $grid->addAction('removeBan');
        $removeBan->setTitle('Remove ban');
        $removeBan->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            if(($row->type == UserProsecutionType::PERMA_BAN || $row->type == UserProsecutionType::BAN) &&
               (strtotime($row->endDate) > time())) {
                return true;
            } else {
                return false;
            }
        };
        $removeBan->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            return LinkBuilder::createSimpleLink('Remove ban', $this->createURL('removeProsecution', ['prosecutionId' => $primaryKey]), 'grid-link');
        };

        return $grid;
    }

    public function renderList() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('Prosecution log', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'logList'], 'post-data-link')
        ];
    }

    public function handleRemoveProsecution(?FormResponse $fr = null) {
        $prosecutionId = $this->httpGet('prosecutionId', true);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $fr->reason;
            $password = $fr->password;

            try {
                $this->app->userAuth->authUser($password);
            } catch(AException $e) {
                $this->flashMessage('Could not authenticate user. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
            }

            $prosecution = $this->app->userProsecutionRepository->getProsecutionById($prosecutionId);
            
            try {
                $user = $this->app->userManager->getUserById($prosecution->getUserId());

                $this->app->userProsecutionRepository->beginTransaction();

                $this->app->userProsecutionManager->removeBan($prosecution->getUserId(), $this->getUserId(), $reason);

                $this->app->userProsecutionRepository->commit($this->getUserId(), __METHOD__);
                
                $this->flashMessage('Removed ban for user \'' . $user->getUsername() . '\' (' . $user->getId() . ').');
            } catch(AException $e) {
                $this->app->userProsecutionRepository->rollback();
                
                $this->flashMessage('Could not remove ban for user \'' . $user->getUsername() . '\' (' . $user->getId() . '). Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
            }

            $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'removeProsecution', 'isSubmit' => '1', 'prosecutionId' => $prosecutionId])
                ->addTextArea('reason', 'Reason:', null, true)
                ->addPassword('password', 'Password:', null, true)
                ->addPassword('passwordCheck', 'Password again:', null, true)
                ->addSubmit('Remove ban', false, true)
                ->addJSHandler('js/UserUnbanFormHandler.js')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderRemoveProsecution() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function createComponentGridLog() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->userProsecutionRepository->composeQueryForProsecutionLogHistory(), 'historyId');
        $grid->setGridName(GridHelper::GRID_USER_PROSECUTION_LOG);

        $grid->addColumnText('userId', 'User');
        $grid->addColumnText('commentText', 'Text');
        $grid->addColumnDatetime('dateCreated', 'Date created');

        return $grid;
    }

    public function renderLogList() {
        $this->template->links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list'], 'post-data-link')
        ];
    }
}

?>