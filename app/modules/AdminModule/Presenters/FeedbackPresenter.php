<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Constants\SuggestionStatus;

class FeedbackPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('FeedbackPresenter', 'Feedback');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createFeedbackSidebar();
        });
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