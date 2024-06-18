<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Constants\SystemStatus;
use App\Modules\APresenter;

class ManagePresenter extends APresenter {
    public function __construct() {
        parent::__construct('ManagePresenter', 'Manage');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Manage', 'action' => 'dashboard'], true);
        $sb->addLink('Users', ['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        $sb->addLink('User prosecution', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list']);
        $sb->addLink('System status', ['page' => 'AdminModule:ManageSystemStatus', 'action' => 'list']);

        return $sb->render();
    }

    public function handleDashboard() {
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
                        </div>
                    </div>
                </div>
            ';
        }

        $this->saveToPresenterCache('statusCode', implode('', $statusCode));
    }

    public function renderDashboard() {
        $widget1 = $this->loadFromPresenterCache('statusCode');

        $this->template->widget1 = $widget1;
    }
}

?>