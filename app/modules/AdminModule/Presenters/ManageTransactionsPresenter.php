<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Helpers\GridHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;

class ManageTransactionsPresenter extends AAdminPresenter {
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

    public function renderList() {
        $this->template->links = [];
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->transactionLogRepository->composeQueryForTransactions(), 'transactionId');
        $grid->setGridName(GridHelper::GRID_TRANSACTION_LOG);

        $col = $grid->addColumnText('methodName', 'Method');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, mixed $value) {
            return $row->methodName . '()';
        };
        $grid->addColumnUser('userId', 'User');
        $grid->addColumnDatetime('dateCreated', 'Date created');

        $grid->enableExport();
        
        return $grid;
    }
}

?>