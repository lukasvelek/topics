<?php

namespace App\Modules\AdminModule;

use App\Constants\SystemServiceStatus;
use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\SystemServiceEntity;
use App\Helpers\DateTimeFormatHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\GridBuilder\Row;
use App\UI\LinkBuilder;

class ManageSystemServicesPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageSystemServicesPresenter', 'Manage system services');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });

        global $app;

        if(!$app->sidebarAuthorizator->canManageSystemStatus($app->currentUser->getId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'AdminModule:ManageSystemServices', 'action' => 'loadServicesGrid'])
            ->setMethod('GET')
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName('getServicesGrid')
            ->setFunctionArguments(['_page'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb->build());
        $this->addScript('getServicesGrid(0)');
    }

    public function renderList() {}

    public function actionLoadServicesGrid() {
        global $app;

        $page = $this->httpGet('gridPage');
        $gridSize = $app->cfg['GRID_SIZE'];

        $services = $app->systemServicesRepository->getAllServices();
        $count = count($services);
        $lastPage = ceil($count / $gridSize);

        $gb = new GridBuilder();
        $gb->addColumns(['title' => 'Title', 'dateStarted' => 'Date started', 'dateEnded' => 'Date finished', 'status' => 'Status']);
        $gb->addDataSource($services);
        $gb->addOnColumnRender('status', function(Cell $cell, SystemServiceEntity $sse) {
            $cell->setValue(SystemServiceStatus::toString($sse->getStatus()));

            if($sse->getStatus() == SystemServiceStatus::RUNNING) {
                $cell->setTextColor('green');
            } else {
                $cell->setTextColor('red');
            }

            return $cell;
        });
        $gb->addOnColumnRender('dateStarted', function(Cell $cell, SystemServiceEntity $sse) {
            return DateTimeFormatHelper::formatDateToUserFriendly($sse->getDateStarted());
        });
        $gb->addOnColumnRender('dateEnded', function(Cell $cell, SystemServiceEntity $sse) {
            return DateTimeFormatHelper::formatDateToUserFriendly($sse->getDateEnded());
        });
        $gb->addAction(function(SystemServiceEntity $sse) {
            $text = '-';

            if($sse->getStatus() == SystemServiceStatus::NOT_RUNNING) {
                $text = LinkBuilder::createSimpleLink('Run', ['page' => 'AdminModule:ManageSystemServices', 'action' => 'run', 'serviceId' => $sse->getId()], 'post-data-link');
            }

            return $text;
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $count, 'getServicesGrid');

        foreach($services as $service) {
            $date = new DateTime();
            $date->modify('-2d');
            
            if(strtotime($service->getDateEnded()) < strtotime($date->getResult())) {
                $gb->addOnRowRender($service->getId(), function(Row $row) {
                    $row->setBackgroundColor('orange');
                    $row->setDescription('This service has not run in 2 days or more.');
                    return $row;
                });
            }
        }

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleRun() {
        global $app;

        $serviceId = $this->httpGet('serviceId', true);
        $service = $app->systemServicesRepository->getServiceById($serviceId);

        $app->serviceManager->runService($service->getScriptPath());
        
        $this->flashMessage('Service started.', 'success');
        $this->redirect(['page' => 'AdminModule:ManageSystemServices', 'action' => 'list']);
    }
}

?>