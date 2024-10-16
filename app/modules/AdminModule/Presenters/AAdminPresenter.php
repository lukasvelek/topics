<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\APresenter;

abstract class AAdminPresenter extends APresenter {
    protected function __construct(string $name, string $title) {
        parent::__construct($name, $title);

        $this->moduleName = 'AdminModule';
    }

    public function startup() {
        parent::startup();
        
        $this->addBeforeRenderCallback(function() {
            if(str_contains($this->name, 'Manage')) {
                $this->template->sidebar = $this->createManageSidebar();
            } else {
                $this->template->sidebar = $this->createFeedbackSidebar();
            }
        });
    }

    protected function createFeedbackSidebar() {
        $dashboard = $this->checkPage('AdminModule:Feedback');
        $suggestions = $this->checkPage('AdminModule:FeedbackSuggestions');
        $reports = $this->checkPage('AdminModule:FeedbackReports');

        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Feedback', 'action' => 'dashboard'], $dashboard);

        if($this->app->sidebarAuthorizator->canManageSuggestions($this->getUserId())) {
            $sb->addLink('Suggestions', ['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'list'], $suggestions);
        }
        if($this->app->sidebarAuthorizator->canManageReports($this->getUserId())) {
            $sb->addLink('Reports', ['page' => 'AdminModule:FeedbackReports', 'action' => 'list'], $reports);
        }

        return $sb->render();
    }

    protected function createManageSidebar() {
        $dashboard = $this->checkPage('AdminModule:Manage');
        $users = $this->checkPage('AdminModule:ManageUsers');
        $userProsecutions = $this->checkPage('AdminModule:ManageUserProsecutions');
        $systemStatus = $this->checkPage('AdminModule:ManageSystemStatus');
        $groups = $this->checkPage('AdminModule:ManageGroups');
        $bannedWords = $this->checkPage('AdminModule:ManageBannedWords');
        $systemServices = $this->checkPage('AdminModule:ManageSystemServices');
        $postFileUploads = $this->checkPage('AdminModule:ManagePostFileUploads');
        $transactions = $this->checkPage('AdminModule:ManageTransactions');
        $gridExports = $this->checkPage('AdminModule:ManageGridExports');
        $emails = $this->checkPage('AdminModule:ManageEmails');

        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Manage', 'action' => 'dashboard'], $dashboard);

        if($this->app->sidebarAuthorizator->canManageUsers($this->getUserId())) {
            $sb->addLink('Users', $this->createFullURL('AdminModule:ManageUsers', 'list'), $users);
            $sb->addLink('Groups', $this->createFullURL('AdminModule:ManageGroups', 'list'), $groups);
        }
        if($this->app->sidebarAuthorizator->canManageUserProsecutions($this->getUserId())) {
            $sb->addLink('User prosecution', $this->createFullURL('AdminModule:ManageUserProsecutions', 'list'), $userProsecutions);
        }
        if($this->app->sidebarAuthorizator->canManageSystemStatus($this->getUserId())) {
            $sb->addLink('System status', $this->createFullURL('AdminModule:ManageSystemStatus', 'list'), $systemStatus);
            $sb->addLink('System services', $this->createFullURL('AdminModule:ManageSystemServices', 'list'), $systemServices);
        }
        if($this->app->sidebarAuthorizator->canManageBannedWords($this->getUserId())) {
            $sb->addLink('Banned words', $this->createFullURL('AdminModule:ManageBannedWords', 'list'), $bannedWords);
        }
        if($this->app->sidebarAuthorizator->canManagePostFileUploads($this->getUserId())) {
            $sb->addLink('Post file uploads', $this->createFullURL('AdminModule:ManagePostFileUploads', 'list'), $postFileUploads);
        }
        if($this->app->sidebarAuthorizator->canManageTransactions($this->getUserId())) {
            $sb->addLink('Transactions', $this->createFullURL('AdminModule:ManageTransactions', 'list'), $transactions);
        }
        if($this->app->sidebarAuthorizator->canManageGridExports($this->getUserId())) {
            $sb->addLink('Grid exports', $this->createFullURL('AdminModule:ManageGridExports', 'list'), $gridExports);
        }
        if($this->app->sidebarAuthorizator->canManageEmails($this->getUserId())) {
            $sb->addLink('Email queue', $this->createFullURL('AdminModule:ManageEmails', 'list'), $emails);
        }

        return $sb->render();
    }

    private function checkPage(string $page) {
        return $this->httpGet('page') == $page;
    }
}

?>