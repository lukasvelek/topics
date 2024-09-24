<?php

namespace App\Modules\AdminModule;

use App\Constants\SystemServiceStatus;
use App\Core\AjaxRequestBuilder;
use App\Core\Datetypes\DateTime;
use App\Entities\SystemServiceEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\GridBuilder\Row;
use App\UI\LinkBuilder;

class ManageSystemServicesPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageSystemServicesPresenter', 'Manage system services');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });

        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());

        if(!$this->app->sidebarAuthorizator->canManageSystemStatus($this->getUserId())) {
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
        $this->addScript('getServicesGrid(-1)');
    }

    public function renderList() {}

    public function actionLoadServicesGrid() {
        $gridPage = $this->httpGet('gridPage');
        $gridSize = $gridSize = $this->app->getGridSize();

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_SYSTEM_SERVICES, $gridPage);

        $services = $this->app->systemServicesRepository->getAllServices();
        $count = count($services);
        $lastPage = ceil($count / $gridSize);

        $gb = new GridBuilder();
        $gb->addColumns(['title' => 'Title', 'dateStarted' => 'Date started', 'dateEnded' => 'Date finished', 'runTime' => 'Time run', 'status' => 'Status']);
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
        $gb->addOnColumnRender('runTime', function(Cell $cell, SystemServiceEntity $sse) {
            $text = '-';
            $color = 'black';
            $title = '';

            if($sse->getStatus() == SystemServiceStatus::RUNNING) {
                if($sse->getDateStarted() !== null) {
                    $end = time();
                    $start = strtotime($sse->getDateStarted());

                    $diff = $end - $start;

                    try {
                        $text = DateTimeFormatHelper::formatSecondsToUserFriendly($diff);
                        $color = 'orange';
                        $title = 'As of now';
                    } catch(AException $e) {}
                }
            } else {
                if($sse->getDateStarted() !== null && $sse->getDateEnded() !== null) {
                    $end = strtotime($sse->getDateEnded());
                    $start = strtotime($sse->getDateStarted());

                    $diff = $end - $start;

                    try {
                        $text = DateTimeFormatHelper::formatSecondsToUserFriendly($diff);
                    } catch(AException $e) {}
                }
            }

            $cell->setTextColor($color);
            $cell->setValue($text);
            $cell->setTitle($title);

            return $cell;
        });
        $gb->addAction(function(SystemServiceEntity $sse) {
            $text = '-';

            if($sse->getStatus() == SystemServiceStatus::NOT_RUNNING && $sse->getTitle() != 'PostLikeEqualizer') {
                $text = LinkBuilder::createSimpleLink('Run', ['page' => 'AdminModule:ManageSystemServices', 'action' => 'run', 'serviceId' => $sse->getId()], 'grid-link');
            }

            return $text;
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $count, 'getServicesGrid');

        foreach($services as $service) {
            $date = new DateTime();
            $date->modify('-2d');
            
            if($service->getStatus() == SystemServiceStatus::NOT_RUNNING && strtotime($service->getDateEnded()) < strtotime($date->getResult())) {
                $gb->addOnRowRender($service->getId(), function(Row $row) {
                    $row->setBackgroundColor('orange');
                    $row->setDescription('This service has not run in 2 days or more.');
                    return $row;
                });
            }
        }

        return ['grid' => $gb->build()];
    }

    public function handleRun() {
        $serviceId = $this->httpGet('serviceId', true);
        $service = $this->app->systemServicesRepository->getServiceById($serviceId);

        $this->app->serviceManager->runService($service->getScriptPath());  
        
        $this->flashMessage('Service started.', 'success');
        $this->redirect(['page' => 'AdminModule:ManageSystemServices', 'action' => 'list']);
    }
}

?>