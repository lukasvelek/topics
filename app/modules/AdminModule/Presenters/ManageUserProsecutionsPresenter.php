<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\APresenter;

class ManageUserProsecutionsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('ManageUserProsecutionsPresenter', 'Manage user prosecutions');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Manage', 'action' => 'dashboard']);
        $sb->addLink('Users', ['page' => 'AdminModule:ManageUsers', 'action' => 'list']);
        $sb->addLink('User prosecution', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list'], true);
        $sb->addLink('System status', ['page' => 'AdminModule:ManageSystemStatus', 'action' => 'list']);

        return $sb->render();
    }

    public function handleList() {
        global $app;

        $filterKey = 'null';
        $filterType = 'null';

        $gridScript = '<script type="text/javascript">getUserProsecutions(0, ' . $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('gridScript', $gridScript);
    }

    public function renderList() {
        $gridScript = $this->loadFromPresenterCache('gridScript');

        $this->template->grid_script = $gridScript;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $this->template->links = [''];
    }
}

?>