<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Constants\SystemStatus;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\Label;
use App\UI\FormBuilder\Select;

class ManageSystemStatusPresenter extends APresenter {
    public function __construct() {
        parent::__construct('ManageSystemStatusPresenter', 'Manage system status');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        $sb->addLink('Users', ['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        $sb->addLink('System status', ['page' => 'AdminModule:ManageSystemStatus', 'action' => 'list'], true);

        return $sb->render();
    }

    public function handleList() {
        global $app;

        $statuses = $app->systemStatusRepository->getAllStatuses();

        $statusCode = [];
        foreach($statuses as $status) {
            $statusText = SystemStatus::toString($status->getStatus());
            $color = SystemStatus::getColorByCode($status->getStatus());

            $description = '';

            if($status->getDescription() !== null) {
                $description = '<p style="font-size: 14px">' . $status->getDescription() . '</p>';
            }

            $statusCode[] = '
                <div class="row">
                    <div class="col-md">
                        <div class="system-status-item">
                            <span class="system-status-item-title" style="font-size: 20px; margin-right: 10px">' . $status->getName() . '</span>
                            <span style="font-size: 16px"><span style="color: ' . $color . '; font-size: 23px">&#x25cf;</span> ' . $statusText . '</span>
                            ' . $description . '
                            <a class="system-status-item-link" href="?page=AdminModule:ManageSystemStatus&action=form&systemId=' . $status->getId() . '">Update</a>
                        </div>
                    </div>
                </div>
            ';
        }

        $this->saveToPresenterCache('statusCode', implode('', $statusCode));
    }

    public function renderList() {
        $list = $this->loadFromPresenterCache('statusCode');

        $this->template->list = $list;
    }

    public function handleForm() {
        global $app;

        $systemId = $this->httpGet('systemId');
        $system = $app->systemStatusRepository->getSystemStatusById($systemId);
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $status = $this->httpPost('status');
            $description = $this->httpPost('description');

            if($description == '' || empty($description)) {
                $description = null;
            }

            $app->logger->warning('User #' . $app->currentUser->getId() . ' changed status for system #' . $systemId . ' from \'' . SystemStatus::toString($system->getStatus()) . '\' to \'' . SystemStatus::toString($status) . '\'.', __METHOD__);

            $app->systemStatusRepository->updateStatus($systemId, $status, ($description === null));

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
        }
    }

    public function renderForm() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form->render();
    }
}

?>