<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\APresenter;

class FeedbackReportsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('FeedbackReportsPresenter', 'Reports');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Feedback', 'action' => 'dashboard']);
        $sb->addLink('Suggestions', ['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'list']);
        $sb->addLink('Reports', ['page' => 'AdminModule:FeedbackReports', 'action' => 'list'], true);

        return $sb->render();
    }

    public function handleList() {
        global $app;

        $filterType = $this->httpGet('filterType') ?? 'null';
        $filterKey = $this->httpGet('filterKey') ?? 'null';

        $this->saveToPresenterCache('list', '<script type="text/javascript">loadReports(10, 0, ' . $app->currentUser->getId() . ', \'' . $filterType . '\', \'' . $filterKey . '\')</script><div id="report-list"></div><div id="report-list-link"></div><br>');
    }

    public function renderList() {
        $list = $this->loadFromPresenterCache('list');

        $this->template->reports = $list;
    }
}

?>