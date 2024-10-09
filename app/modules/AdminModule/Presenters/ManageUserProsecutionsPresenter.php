<?php

namespace App\Modules\AdminModule;

use App\Constants\UserProsecutionType;
use App\Core\AjaxRequestBuilder;
use App\Entities\UserProsecutionEntity;
use App\Entities\UserProsecutionHistoryEntryEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\LinkBuilder;

class ManageUserProsecutionsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageUserProsecutionsPresenter', 'Manage user prosecutions');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageUserProsecutions($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
        
        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }

    public function actionProsecutionGrid() {
        $gridPage = $this->httpGet('gridPage');
        $gridSize = $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_USER_PROSECUTIONS, $gridPage);

        $prosecutionCount = $this->app->userProsecutionRepository->getActiveProsecutionsCount();
        $lastPage = ceil($prosecutionCount / $gridSize);
        $prosecutions = $this->app->userProsecutionRepository->getActiveProsecutionsForGrid($gridSize, ($page * $gridSize));

        $gb = $this->getGridBuilder();
        $gb->addColumns(['user' => 'User', 'reason' => 'Reason', 'type' => 'Type', 'dateFrom' => 'Date from', 'dateTo' => 'Date to']);
        $gb->addDataSource($prosecutions);
        $gb->addOnColumnRender('user', function(Cell $cell, UserProsecutionEntity $userProsecution) {
            try {
                $user = $this->app->userManager->getUserById($userProsecution->getUserId());
                return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], 'grid-link');
            } catch(AException $e) {
                return '-';
            }
        });
        $gb->addOnColumnRender('type', function(Cell $cell, UserProsecutionEntity $userProsecution) {
            return UserProsecutionType::toString($userProsecution->getType());
        });
        $gb->addOnColumnRender('dateFrom', function(Cell $cell, UserProsecutionEntity $userProsecution) {
            if($userProsecution->getStartDate() !== null) {
                $cell->setValue(DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getStartDate()));
                $cell->setTitle(DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getStartDate(), DateTimeFormatHelper::ATOM_FORMAT));
            } else {
                $cell->setValue('-');
            }

            return $cell;
        });
        $gb->addOnColumnRender('dateTo', function(Cell $cell, UserProsecutionEntity $userProsecution) {
            if($userProsecution->getEndDate() !== null) {
                $cell->setValue(DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getEndDate()));
                $cell->setTitle(DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getEndDate(), DateTimeFormatHelper::ATOM_FORMAT));
            } else {
                $cell->setValue('-');
            }

            return $cell;
        });
        $gb->addAction(function(UserProsecutionEntity $userProsecution) {
            if(($userProsecution->getType() == UserProsecutionType::PERMA_BAN || $userProsecution->getType() == UserProsecutionType::BAN) && 
                (strtotime($userProsecution->getEndDate()) > time())) {
                return LinkBuilder::createSimpleLink('Remove ban', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'removeProsecution', 'prosecutionId' => $userProsecution->getId()], 'grid-link');
            } else {
                return '-';
            }
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $prosecutionCount, 'getUserProsecutions');

        $gb->addOnExportRender('user', function(UserProsecutionEntity $userProsecution) {
            try {
                $user = $this->app->userManager->getUserById($userProsecution->getUserId());
                return $user->getUsername();
            } catch(AException $e) {
                return '-';
            }
        });
        $gb->addOnExportRender('type', function(UserProsecutionEntity $userProsecution) {
            return UserProsecutionType::toString($userProsecution->getType());
        });
        $gb->addOnExportRender('dateFrom', function(UserProsecutionEntity $userProsecution) {
            if($userProsecution->getStartDate() !== null) {
                return DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getStartDate());
            } else {
                return '-';
            }
        });
        $gb->addOnExportRender('dateTo', function(UserProsecutionEntity $userProsecution) {
            if($userProsecution->getEndDate() !== null) {
                return DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getEndDate());
            } else {
                return '-';
            }
        });
        $gb->addGridExport(function() {
            return $this->app->userProsecutionRepository->getActiveProsecutionsForGrid(0, 0);
        }, GridHelper::GRID_USER_PROSECUTIONS, $this->logger);

        return ['grid' => $gb->build()];
    }
    
    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setMethod('get')
            ->setFunctionName('getUserProsecutions')
            ->setFunctionArguments(['_page'])
            ->setURL(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'prosecutionGrid'])
            ->updateHTMLElement('grid-content', 'grid')
            ->setHeader(['gridPage' => '_page']);

        $this->addScript($arb->build());
        $this->addScript('getUserProsecutions(-1)');

        $links = [
            LinkBuilder::createSimpleLink('Prosecution log', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'logList'], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
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

    public function actionProsecutionLogGrid() {
        $gridPage = $this->httpGet('gridPage');
        $gridSize = $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_USER_PROSECUTION_LOG, $gridPage);

        $historyEntriesCount = $this->app->userProsecutionRepository->getProsecutionHistoryEntryCount();
        $lastPage = ceil($historyEntriesCount / $gridSize);
        $historyEntries = $this->app->userProsecutionRepository->getProsecutionHistoryEntriesForGrid($gridSize, ($page * $gridSize));

        $gb = $this->getGridBuilder();
        $gb->addColumns(['user' => 'User', 'text' => 'Text', 'dateCreated' => 'Date created']);
        $gb->addDataSource($historyEntries);
        $gb->addOnColumnRender('user', function (Cell $cell, UserProsecutionHistoryEntryEntity $entity) {
            try {
                $user = $this->app->userManager->getUserById($entity->getUserId());
                return LinkBuilder::createSimpleLink($user->getUsername(), ['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $user->getId()], 'grid-link');
            } catch(AException $e) {
                return '-';
            }
        });
        $gb->addOnColumnRender('dateCreated', function(Cell $cell, UserProsecutionHistoryEntryEntity $entity) {
            return DateTimeFormatHelper::formatDateToUserFriendly($entity->getDateCreated());
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $historyEntriesCount, 'getProsecutionLog');

        return ['grid' => $gb->build()];
    }

    public function handleLogList() {
        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'prosecutionLogGrid'])
            ->setFunctionName('getProsecutionLog')
            ->setFunctionArguments(['_page'])
            ->setHeader(['gridPage' => '_page'])
            ->setMethod('GET')
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getProsecutionLog(-1)');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list'], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderLogList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }
}

?>