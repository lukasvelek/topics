<?php

namespace App\Modules\AdminModule;

class ManageGroupsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageGroupsPresenter', 'Group management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }

    public function handleList() {
        global $app;

        $script = '<script type="text/javascript">getGroups(0, '. $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('grid', $script);
    }

    public function renderList() {
        $grid = $this->loadFromPresenterCache('grid');

        $this->template->grid_script = $grid;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $this->template->links = [];
    }

    public function handleListMembers() {
        global $app;

        $groupId = $this->httpGet('groupId', true);

        $script = '<script type="text/javascript">getGroupMembers(0, ' . $groupId . ', '. $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('grid', $script);
    }

    public function renderListMembers() {
        $grid = $this->loadFromPresenterCache('grid');

        $this->template->grid_script = $grid;
        $this->template->grid = '';
        $this->template->grid_paginator = '';

        $this->template->links = [];
    }
}

?>