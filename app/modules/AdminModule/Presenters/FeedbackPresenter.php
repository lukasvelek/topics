<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Constants\SuggestionStatus;
use App\Modules\APresenter;

class FeedbackPresenter extends APresenter {
    public function __construct() {
        parent::__construct('FeedbackPresenter', 'Feedback');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Feedback', 'action' => 'dashboard'], true);
        $sb->addLink('Suggestions', ['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'list']);
        $sb->addLink('Reports', ['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);

        return $sb->render();
    }

    public function handleDashboard() {
        global $app;

        $openSuggestionCount = $app->suggestionRepository->getOpenSuggestionCount();
        $newSuggestionCount = $app->suggestionRepository->getSuggestionCountByStatuses([SuggestionStatus::OPEN]);

        $widget1Code = '
            <div>
                <p class="post-data">Open suggestions: ' . $openSuggestionCount . '</p>
                <p class="post-data">New suggestions: ' . $newSuggestionCount . '</p>
            </div>
        ';

        $this->saveToPresenterCache('widget1', $widget1Code);
    }

    public function renderDashboard() {
        $widget1 = $this->loadFromPresenterCache('widget1');

        $this->template->widget1 = $widget1;
    }
}

?>