<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Modules\APresenter;

class FeedbackSuggestionsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('FeedbackSuggestionsPresenter', 'Suggestions');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Feedback', 'action' => 'dashboard']);
        $sb->addLink('Suggestions', ['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'list'], true);
        $sb->addLink('Reports', ['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);

        return $sb->render();
    }

    public function handleList() {
        global $app;

        $filterType = $this->httpGet('filterType') ?? 'null';
        $filterKey = $this->httpGet('filterKey') ?? 'null';

        $this->saveToPresenterCache('list', '<script type="text/javascript">loadSuggestions(10, 0, ' . $app->currentUser->getId() . ', \'' . $filterType . '\', \'' . $filterKey . '\')</script><div id="suggestion-list"></div><div id="suggestion-list-link"></div><br>');
    }

    public function renderList() {
        $list = $this->loadFromPresenterCache('list');

        $this->template->suggestions = $list;
    }
}

?>