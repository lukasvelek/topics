<?php

namespace App\Modules\AdminModule;

use App\Core\DB\DatabaseRow;
use App\Helpers\GridHelper;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;

class ManageGridExportsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageGridExportsPresenter', 'Manage grid exports');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageGridExports($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function handleList() {}

    public function renderList() {
        $this->template->links = [];
    }

    protected function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->gridExportRepository->composeQueryForExports(), 'exportId');
        $grid->setGridName(GridHelper::GRID_GRID_EXPORTS);
        
        $grid->addColumnUser('userId', 'User');
        $grid->addColumnDatetime('dateCreated', 'Date started');
        $grid->addColumnDatetime('dateFinished', 'Date finished');
        $grid->addColumnText('entryCount', 'Entries');
        $col = $grid->addColumnText('timeTaken', 'Seconds taken');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            if($value === null) {
                $value = '0 s';
            } else {
                $value = $value . ' s';
            }

            return $value;
        };

        $downloadFile = $grid->addAction('downloadFile');
        $downloadFile->setTitle('Download file');
        $downloadFile->onCanRender[] = function(DatabaseRow $row, Row $_row) {
            return $row->dateFinished !== null;
        };
        $downloadFile->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            $el = HTML::el('a')
                    ->href($row->filename)
                    ->text('Download')
                    ->class('grid-link')
                    ->addAtribute('target', '_blank');

            return $el;
        };

        return $grid;
    }
}

?>