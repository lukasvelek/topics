<?php

namespace App\Modules\AdminModule;

use App\UI\LinkBuilder;

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
        $group = $app->groupRepository->getGroupById($groupId);

        $this->saveToPresenterCache('group', $group);

        $script = '<script type="text/javascript">getGroupMembers(0, ' . $groupId . ', '. $app->currentUser->getId() . ')</script>';

        $this->saveToPresenterCache('grid', $script);

        $links = [];

        if($app->actionAuthorizator->canAddMemberToGroup($app->currentUser->getId())) {
            $links[] = LinkBuilder::createSimpleLink('Add member', ['page' => 'AdminModule:ManageGroups', 'action' => 'newMember', 'groupId' => $groupId], 'post-data-link');
        }

        $this->saveToPresenterCache('links', $links);
    }

    public function renderListMembers() {
        $grid = $this->loadFromPresenterCache('grid');
        $links = $this->loadFromPresenterCache('links');
        $group = $this->loadFromPresenterCache('group');

        $this->template->grid_script = $grid;
        $this->template->grid = '';
        $this->template->grid_paginator = '';
        $this->template->links = $links;
        $this->template->group_title = $group->getTitle();
    }
}

?>