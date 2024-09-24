<?php

namespace App\Modules\AdminModule;

use App\Constants\SystemStatus;
use App\Core\AjaxRequestBuilder;
use App\Entities\SystemStatusEntity;
use App\Exceptions\AException;
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

        if(!$this->app->sidebarAuthorizator->canManageSystemStatus($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function actionCreateGrid() {
        $gb = new GridBuilder();

        $statuses = $this->app->systemStatusRepository->getAllStatuses();

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

        return ['grid' => $gb->build()];
    }

    public function handleList() {
        $arb = new AjaxRequestBuilder();

        $arb->setURL($this->createURL('createGrid'))
            ->setMethod()
            ->setFunctionName('createGrid')
            ->updateHTMLElement('grid-content', 'grid');

        $this->addScript($arb->build());
        $this->addScript('createGrid()');
    }

    public function renderList() {}

    public function handleForm(?FormResponse $fr = null) {
        $systemId = $this->httpGet('systemId');
        $system = $this->app->systemStatusRepository->getSystemStatusById($systemId);
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $status = $fr->status;
            $description = $fr->description;

            if($description == '' || empty($description)) {
                $description = null;
            }

            try {
                $this->app->systemStatusRepository->beginTransaction();
                
                $this->app->systemStatusRepository->updateStatus($systemId, $status, $description);

                $this->app->logger->warning('User #' . $this->getUserId() . ' changed status for system #' . $systemId . ' from \'' . SystemStatus::toString($system->getStatus()) . '\' to \'' . SystemStatus::toString($status) . '\'.', __METHOD__);
                $this->app->logger->warning('User #' . $this->getUserId() . ' changed description for system #' . $systemId . ' from \'' . $system->getDescription() . '\' to \'' . $description . '\'.', __METHOD__);

                $this->app->systemStatusRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('System status updated.', 'success');
            } catch(AException $e) {
                $this->app->systemStatusRepository->rollback();

                $this->flashMessage('Could not update system status. Reason: ' . $e->getMessage(), 'error');
            }

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
                ->addSubmit('Save', false, true)
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