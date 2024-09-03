<?php

namespace App\Modules\AdminModule;

use App\Core\AjaxRequestBuilder;
use App\Entities\TransactionEntity;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;

class ManageTransactionsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        global $app;

        parent::__construct('ManageTransactionsPresenter', 'Manage transactions');

        $this->gridHelper = new GridHelper($app->logger, $app->currentUser->getId());

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function handleList() {
        $links = [];

        $this->saveToPresenterCache('links', $links);

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setHeader(['gridPage' => '_gridPage'])
            ->setAction($this, 'getGrid')
            ->setFunctionName('getGrid')
            ->setFunctionArguments(['_gridPage'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb);
        $this->addScript('getGrid(-1)');
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function actionGetGrid() {
        global $app;

        $gridSize = $app->getGridSize();
        $gridPage = $this->httpGet('gridPage');

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_TRANSACTION_LOG, $gridPage);
        
        $transactions = $app->transactionLogRepository->getTransactionsForGrid($gridSize, ($page * $gridSize));
        $totalCount = count($app->transactionLogRepository->getTransactionsForGrid(0, 0));

        $lastPage = ceil($totalCount / $gridSize);

        $gb = new GridBuilder();
        
        $gb->setIdElement('gridbuilder-grid2');
        $gb->addColumns(['method' => 'Method', 'user' => 'User', 'dateCreated' => 'Date created']);
        $gb->addDataSource($transactions);
        $gb->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getGrid');
        $gb->addOnColumnRender('method', function(Cell $cell, TransactionEntity $te) {
            return $te->getMethod() . '()';
        });
        $gb->addOnColumnRender('user', function(Cell $cell, TransactionEntity $te) use ($app) {
            if($te->getUserId() === null) {
                return '-';
            }
            
            $user = $app->userRepository->getUserById($te->getUserId());

            if($user === null) {
                return '-';
            }

            $a = HTML::a();

            $a->href($this->createFullURLString('UserModule:Users', 'profile', ['userId' => $te->getUserId()]))
                ->text($user->getUsername())
                ->class('grid-link')
            ;

            return $a->render();
        });
        $gb->addOnColumnRender('dateCreated', function(Cell $cell, TransactionEntity $te) {
            return DateTimeFormatHelper::formatDateToUserFriendly($te->getDateCreated());
        });

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }
}

?>