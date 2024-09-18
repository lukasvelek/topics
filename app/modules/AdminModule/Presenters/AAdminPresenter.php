<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\APresenter;

abstract class AAdminPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'AdminModule';
    }

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
        $groups = $this->checkPage('AdminModule:ManageGroups');
        $deletedContent = $this->checkPage('AdminModule:ManageDeletedContent');
        $bannedWords = $this->checkPage('AdminModule:ManageBannedWords');
        $systemServices = $this->checkPage('AdminModule:ManageSystemServices');
        $postFileUploads = $this->checkPage('AdminModule:ManagePostFileUploads');
        $transactions = $this->checkPage('AdminModule:ManageTransactions');
        $gridExports = $this->checkPage('AdminModule:ManageGridExports');

        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Manage', 'action' => 'dashboard'], $dashboard);

        if($app->sidebarAuthorizator->canManageUsers($app->currentUser->getId())) {
            $sb->addLink('Users', ['page' => 'AdminModule:ManageUsers', 'action' => 'list'], $users);
            $sb->addLink('Groups', ['page' => 'AdminModule:ManageGroups', 'action' => 'list'], $groups);
        }
        if($app->sidebarAuthorizator->canManageUserProsecutions($app->currentUser->getId())) {
            $sb->addLink('User prosecution', ['page' => 'AdminModule:ManageUserProsecutions', 'action' => 'list'], $userProsecutions);
        }
        if($app->sidebarAuthorizator->canManageSystemStatus($app->currentUser->getId())) {
            $sb->addLink('System status', ['page' => 'AdminModule:ManageSystemStatus', 'action' => 'list'], $systemStatus);
            $sb->addLink('System services', ['page' => 'AdminModule:ManageSystemServices', 'action' => 'list'], $systemServices);
        }
        if($app->sidebarAuthorizator->canManageDeletedContent($app->currentUser->getId())) {
            $sb->addLink('Deleted content', ['page' => 'AdminModule:ManageDeletedContent', 'action' => 'list'], $deletedContent);
        }
        if($app->sidebarAuthorizator->canManageBannedWords($app->currentUser->getId())) {
            $sb->addLink('Banned words', ['page' => 'AdminModule:ManageBannedWords', 'action' => 'list'], $bannedWords);
        }
        if($app->sidebarAuthorizator->canManagePostFileUploads($app->currentUser->getId())) {
            $sb->addLink('Post file uploads', ['page' => 'AdminModule:ManagePostFileUploads', 'action' => 'list'], $postFileUploads);
        }
        if($app->sidebarAuthorizator->canManageTransactions($app->currentUser->getId())) {
            $sb->addLink('Transactions', ['page' => 'AdminModule:ManageTransactions', 'action' => 'list'], $transactions);
        }
        if($app->sidebarAuthorizator->canManageGridExports($app->currentUser->getId())) {
            $sb->addLink('Grid exports', ['page' => 'AdminModule:ManageGridExports', 'action' => 'list'], $gridExports);
        }

        return $sb->render();
    }

    private function checkPage(string $page) {
        return $this->httpGet('page') == $page;
    }
}

?>