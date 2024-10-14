<?php

namespace App\Modules\AdminModule;

use App\Constants\SystemStatus;
use App\Core\DB\DatabaseRow;
use App\Exceptions\AException;
use App\Helpers\GridHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageSystemStatusPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageSystemStatusPresenter', 'Manage system status');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageSystemStatus($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        }
    }

    public function createComponentGrid() {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->systemStatusRepository->composeQueryForStatuses(), 'systemId');
        $grid->setGridName(GridHelper::GRID_SYSTEM_STATUSES);

        $grid->addColumnText('name', 'Name');
        $col = $grid->addColumnText('status', 'Status');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            $html->style('color', SystemStatus::getColorByCode($value));
            return SystemStatus::toString($value);
        };

        $update = $grid->addAction('update');
        $update->setTitle('Update');
        $update->onCanRender[] = function() {
            return true;
        };
        $update->onRender[] = function(mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html) {
            return LinkBuilder::createSimpleLink('Update', $this->createURL('form', ['systemId' => $primaryKey]), 'grid-link');
        };

        return $grid;
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