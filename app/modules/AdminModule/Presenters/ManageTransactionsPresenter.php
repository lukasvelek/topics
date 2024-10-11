<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Entities\TransactionEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ManageTransactionsPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageTransactionsPresenter', 'Manage transactions');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageTransactions($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function handleList() {
        $links = [];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->transactionLogRepository->composeQueryForTransactions(), 'transactionId');

        $col = $grid->addColumnText('methodName', 'Method');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, mixed $value) {
            return $row->methodName . '()';
        };
        $grid->addColumnUser('userId', 'User');
        $grid->addColumnDatetime('dateCreated', 'Date created');
        
        return $grid;
    }
}

?>