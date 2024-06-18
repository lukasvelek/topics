<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\APresenter;

abstract class AAdminPresenter extends APresenter {
    protected function createFeedbackSidebar() {
        global $app;

        $dashboard = $this->checkPage('AdminModule:Feedback');
        $suggestions = $this->checkPage('AdminModule:FeedbackSuggestions');
        $reports = $this->checkPage('AdminModule:FeedbackReports');

        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Feedback', 'action' => 'dashboard'], $dashboard);

        if($app->sidebarAuthorizator->canManageSuggestions($app->currentUser->getId())) {
            $sb->addLink('Suggestions', ['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'list'], $suggestions);
        }
        if($app->sidebarAuthorizator->canManageReports($app->currentUser->getId())) {
            $sb->addLink('Reports', ['page' => 'AdminModule:FeedbackReports', 'action' => 'list'], $reports);
        }

        return $sb->render();
    }

    protected function createManageSidebar() {
        global $app;

        $dashboard = $this->checkPage('AdminModule:Manage');
        $users = $this->checkPage('AdminModule:ManageUsers');
        $userProsecutions = $this->checkPage('AdminModule:ManageUserProsecutions');
        $systemStatus = $this->checkPage('AdminModule:ManageSystemStatus');

        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Manage', 'action' => 'dashboard'], $dashboard);

        if($app->sidebarAuthorizator->canManageUsers($app->currentUser->getId())) {
            $sb->addLink('Users', ['page' => 'AdminModule:ManageUsers', 'action' => 'list'], $users);
        }
        if($app->sidebarAuthorizator->canManageUserProsecutions($app->currentUser->getId())) {
            $sb->addLink('User prosecution', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list'], $userProsecutions);
        }
        if($app->sidebarAuthorizator->canManageSystemStatus($app->currentUser->getId())) {
            $sb->addLink('System status', ['page' => 'AdminModule:ManageSystemStatus', 'action' => 'list'], $systemStatus);
        }

        return $sb->render();
    }

    private function checkPage(string $page) {
        return $this->httpGet('page') == $page;
    }
}

?>