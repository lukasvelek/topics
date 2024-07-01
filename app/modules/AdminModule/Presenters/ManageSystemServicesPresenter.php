<?php

namespace App\Modules\AdminModule;

use App\Constants\SystemServiceStatus;
use App\Core\AjaxRequestBuilder;
use App\Entities\SystemServiceEntity;
use App\UI\GridBuilder\GridBuilder;
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
            ->updateHTMLElement('grid-paginator', 'paginator')
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
        $lastPage = ceil($count / $gridSize) - 1;

        $gb = new GridBuilder();
        $gb->addColumns(['title' => 'Title', 'dateStarted' => 'Date started', 'dateEnded' => 'Date finished']);
        $gb->addDataSource($services);
        $gb->addAction(function(SystemServiceEntity $sse) {
            $text = '-';

            if($sse->getStatus() == SystemServiceStatus::NOT_RUNNING) {
                $text = LinkBuilder::createSimpleLink('Run', ['page' => 'AdminModule:ManageSystemServices', 'action' => 'run', 'serviceId' => $sse->getId()], 'post-data-link');
            }

            return $text;
        });
        
        $paginator = $gb->createGridControls2('getServicesGrid', $page, $lastPage);

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator]);
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