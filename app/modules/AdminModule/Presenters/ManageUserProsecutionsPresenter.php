<?php

namespace App\Modules\AdminModule;

use App\Constants\UserProsecutionType;
use App\Core\AjaxRequestBuilder;
use App\Entities\UserProsecutionEntity;
use App\Entities\UserProsecutionHistoryEntryEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class ManageUserProsecutionsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageUserProsecutionsPresenter', 'Manage user prosecutions');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });

        global $app;

        if(!$app->sidebarAuthorizator->canManageUserProsecutions($app->currentUser->getId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function actionProsecutionGrid() {
        global $app;

        $page = $this->httpGet('gridPage');

        $gridSize = $app->cfg['GRID_SIZE'];

        $prosecutionCount = $app->userProsecutionRepository->getActiveProsecutionsCount();
        $lastPage = ceil($prosecutionCount / $gridSize) - 1;
        $prosecutions = $app->userProsecutionRepository->getActiveProsecutionsForGrid($gridSize, ($page * $gridSize));

        $gb = new GridBuilder();
        $gb->addColumns(['user' => 'User', 'reason' => 'Reason', 'type' => 'Type', 'dateFrom' => 'Date from', 'dateTo' => 'Date to']);
        $gb->addDataSource($prosecutions);
        $gb->addOnColumnRender('user', function(UserProsecutionEntity $userProsecution) use ($app) {
            $user = $app->userRepository->getUserById($userProsecution->getUserId());
            return '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">' . $user->getUsername() . '</a>';
        });
        $gb->addOnColumnRender('type', function(UserProsecutionEntity $userProsecution) {
            return UserProsecutionType::toString($userProsecution->getType());
        });
        $gb->addOnColumnRender('dateFrom', function(UserProsecutionEntity $userProsecution) {
            if($userProsecution->getStartDate() !== null) {
                return DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getStartDate());
            } else {
                return '-';
            }
        });
        $gb->addOnColumnRender('dateTo', function(UserProsecutionEntity $userProsecution) {
            if($userProsecution->getEndDate() !== null) {
                return DateTimeFormatHelper::formatDateToUserFriendly($userProsecution->getEndDate());
            } else {
                return '-';
            }
        });
        $gb->addAction(function(UserProsecutionEntity $userProsecution) {
            if(($userProsecution->getType() == UserProsecutionType::PERMA_BAN || $userProsecution->getType() == UserProsecutionType::BAN) && 
                (strtotime($userProsecution->getEndDate()) > time())) {
                return LinkBuilder::createSimpleLink('Remove ban', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'removeProsecution', 'prosecutionId' => $userProsecution->getId()], 'post-data-link');
            } else {
                return '-';
            }
        });

        $paginator = $gb->createGridControls2('getUserProsecutions', $page, $lastPage);

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator]);
    }
    
    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setMethod('get')
            ->setFunctionName('getUserProsecutions')
            ->setFunctionArguments(['_page'])
            ->setURL(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'prosecutionGrid'])
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-paginator', 'paginator')
            ->setHeader(['gridPage' => '_page']);

        $this->addScript($arb->build());
        $this->addScript('getUserProsecutions(0)');

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
        global $app;

        $prosecutionId = $this->httpGet('prosecutionId', true);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $reason = $fr->reason;
            $password = $fr->password;

            try {
                $app->userAuth->authUser($password);
            } catch(AException $e) {
                $this->flashMessage('Could not authenticate user. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
            }

            $prosecution = $app->userProsecutionRepository->getProsecutionById($prosecutionId);
            $user = $app->userRepository->getUserById($prosecution->getUserId());

            try {
                $app->userProsecutionManager->removeBan($prosecution->getUserId(), $app->currentUser->getId(), $reason);
            } catch(AException $e) {
                $this->flashMessage('Could not remove ban for user \'' . $user->getUsername() . '\' (' . $user->getId() . '). Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
            }

            $this->flashMessage('Removed ban for user \'' . $user->getUsername() . '\' (' . $user->getId() . ').');
            $this->redirect(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'removeProsecution', 'isSubmit' => '1', 'prosecutionId' => $prosecutionId])
                ->addTextArea('reason', 'Reason:', null, true)
                ->addPassword('password', 'Password:', null, true)
                ->addPassword('passwordCheck', 'Password again:', null, true)
                ->addSubmit('Remove ban')
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
        global $app;

        $page = $this->httpGet('gridPage');

        $gridSize = $app->cfg['GRID_SIZE'];

        $historyEntriesCount = $app->userProsecutionRepository->getProsecutionHistoryEntryCount();
        $lastPage = ceil($historyEntriesCount / $gridSize) - 1;
        $historyEntries = $app->userProsecutionRepository->getProsecutionHistoryEntriesForGrid($gridSize, ($page * $gridSize));

        $gb = new GridBuilder();
        $gb->addColumns(['user' => 'User', 'text' => 'Text', 'dateCreated' => 'Date created']);
        $gb->addDataSource($historyEntries);
        $gb->addOnColumnRender('user', function (UserProsecutionHistoryEntryEntity $entity) use ($app) {
            $user = $app->userRepository->getUserById($entity->getUserId());
            return '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $user->getId() . '">' . $user->getUsername() . '</a>';
        });
        $gb->addOnColumnRender('dateCreated', function(UserProsecutionHistoryEntryEntity $entity) {
            return DateTimeFormatHelper::formatDateToUserFriendly($entity->getDateCreated());
        });

        $paginator = $gb->createGridControls2('getProsecutionLog', $page, $lastPage);

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator]);
    }

    public function handleLogList() {
        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'prosecutionLogGrid'])
            ->setFunctionName('getProsecutionLog')
            ->setFunctionArguments(['_page'])
            ->setHeader(['gridPage' => '_page'])
            ->setMethod('GET')
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-paginator', 'paginator')
        ;

        $this->addScript($arb->build());
        $this->addScript('getProsecutionLog(0)');

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