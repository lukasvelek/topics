<?php

namespace App\Modules\AdminModule;

use App\Constants\Systems;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Helpers\GridHelper;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;
use Exception;

class ManageSystemServicesPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageSystemServicesPresenter', 'Manage system services');
    }

    public function startup() {
        parent::startup();
        
        if(!$this->app->sidebarAuthorizator->canManageSystemStatus($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function handleList() {}

    public function renderList() {}

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->systemServicesRepository->composeQueryForServices(), 'serviceId');
        $grid->setGridName(GridHelper::GRID_SYSTEM_SERVICES);

        $grid->addColumnText('title', 'Title');
        $grid->addColumnDatetime('dateStarted', 'Date started');
        $grid->addColumnDatetime('dateEnded', 'Date ended');

        $grid->onRowRender[] = function(DatabaseRow $row, Row $_row, HTML $tr) {
            $date = new DateTime();
            $date->modify('-2d');
            $date = $date->getResult();

            if($row->dateEnded === null || strtotime($row->dateEnded) <= strtotime($date)) {
                $tr->style('background-color', 'orange');
            }
        };

        $systemsOn = [];
        $action = $grid->addAction('run');
        $action->onCanRender[] = function(DatabaseRow $row, Row $_row) use (&$systemsOn){
            if($row->title == 'PostLikeEqualizer') {
                return false;
            }

            try {
                if(array_key_exists(Systems::SYSTEM_SERVICES, $systemsOn)) {
                    if($systemsOn[Systems::SYSTEM_SERVICES] === false) {
                        if(!$this->app->systemStatusManager->isUserSuperAdministrator($this->getUserId())) {
                            return false;
                        }
                    }
                } else {
                    $systemsOn[Systems::SYSTEM_SERVICES] = $this->app->systemStatusManager->isSystemOn(Systems::SYSTEM_SERVICES);

                    if($systemsOn[Systems::SYSTEM_SERVICES] === false) {
                        if(!$this->app->systemStatusManager->isUserSuperAdministrator($this->getUserId())) {
                            return false;
                        }
                    }
                }
            } catch(AException|Exception) {}

            return true;
        };
        $action->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
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

        sleep(1); // because the system services grid would load so fast, that the service didn't have time to start executing and thus the grid would look as if nothing happened and would need to be refreshed
        
        $this->redirect(['page' => 'AdminModule:ManageSystemServices', 'action' => 'list']);
    }
}

?>