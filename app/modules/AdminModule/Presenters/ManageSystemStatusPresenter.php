<?php

namespace App\Modules\AdminModule;

use App\Constants\SystemStatus;
use App\Core\AjaxRequestBuilder;
use App\Entities\SystemStatusEntity;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

class ManageSystemStatusPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageSystemStatusPresenter', 'Manage system status');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });

        global $app;

        if(!$app->sidebarAuthorizator->canManageSystemStatus($app->currentUser->getId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function actionCreateGrid() {
        global $app;

        $gb = new GridBuilder();

        $statuses = $app->systemStatusRepository->getAllStatuses();

        $gb->addDataSource($statuses);
        $gb->addColumns(['name' => 'Name', 'status' => 'Status', 'description' => 'Description']);
        $gb->addOnColumnRender('status', function(Cell $cell, SystemStatusEntity $sse) {
            $cell->setTextColor(SystemStatus::getColorByCode($sse->getStatus()));
            $cell->setValue(SystemStatus::toString($sse->getStatus()));
            return $cell;
        });
        $gb->addAction(function(SystemStatusEntity $sse) {
            return LinkBuilder::createSimpleLink('Update', $this->createURL('form', ['systemId' => $sse->getId()]), 'grid-link');
        });

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setURL($this->createURL('createGrid'))
            ->setMethod()
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName('createGrid')
            ->setFunctionArguments(['_page'])
            ->updateHTMLElement('grid-content', 'grid');

        $this->addScript($arb->build());
        $this->addScript('createGrid(0)');
    }

    public function renderList() {}

    public function handleForm(?FormResponse $fr = null) {
        global $app;

        $systemId = $this->httpGet('systemId');
        $system = $app->systemStatusRepository->getSystemStatusById($systemId);
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $status = $fr->status;
            $description = $fr->description;

            if($description == '' || empty($description)) {
                $description = null;
            }

            $app->logger->warning('User #' . $app->currentUser->getId() . ' changed status for system #' . $systemId . ' from \'' . SystemStatus::toString($system->getStatus()) . '\' to \'' . SystemStatus::toString($status) . '\'.', __METHOD__);
            $app->logger->warning('User #' . $app->currentUser->getId() . ' changed description for system #' . $systemId . ' from \'' . $system->getDescription() . '\' to \'' . $description . '\'.', __METHOD__);

            $app->systemStatusRepository->updateStatus($systemId, $status, $description);

            $this->flashMessage('System status updated.', 'success');
            $this->redirect(['page' => 'AdminModule:ManageSystemStatus', 'action' => 'list']);
        } else {
            $statusArray = [];
            foreach(SystemStatus::getAll() as $code => $text) {
                $tmp = [
                    'value' => $code,
                    'text' => $text
                ];

                if($code == $system->getStatus()) {
                    $tmp['selected'] = 'selected';
                }

                $statusArray[] = $tmp;
            }

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:ManageSystemStatus', 'action' => 'form', 'isSubmit' => '1', 'systemId' => $systemId])
                ->addSelect('status', 'Status:', $statusArray, true)
                ->addTextArea('description', 'Description:', $system->getDescription())
                ->addSubmit('Save')
            ;

            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('list'), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderForm() {
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->links = $links;
    }
}

?>