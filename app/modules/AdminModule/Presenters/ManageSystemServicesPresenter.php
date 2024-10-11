<?php

namespace App\Modules\AdminModule;

use App\Constants\SystemServiceStatus;
use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Entities\SystemServiceEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\GridBuilder2\GridBuilder;
use App\UI\GridBuilder2\Row as GridBuilder2Row;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageSystemServicesPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageSystemServicesPresenter', 'Manage system services');
    }

    public function startup() {
        parent::startup();
        
        if(!$this->app->sidebarAuthorizator->canManageSystemStatus($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
        
        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }

    public function handleList() {}

    public function renderList() {}

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->systemServicesRepository->composeQueryForServices(), 'serviceId');

        $grid->addColumnText('title', 'Title');
        $grid->addColumnDatetime('dateStarted', 'Date started');
        $grid->addColumnDatetime('dateEnded', 'Date ended');

        $grid->onRowRender[] = function(DatabaseRow $row, GridBuilder2Row $_row, HTML $tr) {
            $date = new DateTime();
            $date->modify('-2d');
            $date = $date->getResult();

            if($row->dateEnded === null || strtotime($row->dateEnded) <= strtotime($date)) {
                $tr->style('background-color', 'orange');
            }
        };
        $action = $grid->addAction('run', 'Run');
        $action->onCanRender[] = function(DatabaseRow $row, GridBuilder2Row $_row) {
            if($row->title == 'PostLikeEqualizer') {
                return false;
            }

            return true;
        };
        $action->onRender[] = function(DatabaseRow $row, GridBuilder2Row $_row) {
            return LinkBuilder::createSimpleLink('Run', $this->createURL('run', ['serviceId' => $row->serviceId]), 'grid-link');
        };

        return $grid;
    }

    public function handleRun() {
        $serviceId = $this->httpGet('serviceId', true);
        $service = $this->app->systemServicesRepository->getServiceById($serviceId);

        try {
            $this->app->serviceManager->runService($service->getScriptPath());
            $this->flashMessage('Service started.', 'success');
        } catch(AException $e) {
            $this->flashMessage('Could not start service. Reason: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect(['page' => 'AdminModule:ManageSystemServices', 'action' => 'list']);
    }
}

?>