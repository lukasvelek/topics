<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportStatus;
use App\Constants\SuggestionCategory;
use App\Constants\SuggestionStatus;

class FeedbackPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('FeedbackPresenter', 'Feedback');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createFeedbackSidebar();
        });
    }

    public function handleDashboard() {
        $this->saveToPresenterCache('widget1', $this->createSuggestionWidget());
        $this->saveToPresenterCache('widget2', $this->createReportWidget());
        $this->saveToPresenterCache('widget3', $this->createSuggestionCategoriesWidget());

        $this->addScript('suggestionWidget(); reportWidget(); suggestionCategoriesWidget();');
    }

    public function renderDashboard() {
        $widget1 = $this->loadFromPresenterCache('widget1');
        $widget2 = $this->loadFromPresenterCache('widget2');
        $widget3 = $this->loadFromPresenterCache('widget3');

        $this->template->widget1 = $widget1;
        $this->template->widget2 = $widget2;
        $this->template->widget3 = $widget3;
    }

    private function createSuggestionWidget() {
        $code = '
            <div style="width: 75%"><canvas id="suggestionWidgetGraph"></canvas></div>
        ';

        return $code;
    }

    public function actionGetSuggestionGraphWidgetData() {
        global $app;

        $all = $app->suggestionRepository->getSuggestionCountByStatuses();
        $open = $app->suggestionRepository->getOpenSuggestionCount();
        $closed = $app->suggestionRepository->getSuggestionCountByStatuses([SuggestionStatus::RESOLVED, SuggestionStatus::NOT_PLANNED]);

        $this->ajaxSendResponse(['all' => $all, 'open' => $open, 'closed' => $closed]);
    }

    private function createReportWidget() {
        $code = '
            <div style="width: 75%"><canvas id="reportWidgetGraph"></canvas></div>
        ';

        return $code;
    }

    public function actionGetReportGraphWidgetData() {
        global $app;

        $all = $app->reportRepository->getReportCountByStatuses();
        $open = $app->reportRepository->getReportCountByStatuses([ReportStatus::OPEN]);
        $closed = $app->reportRepository->getReportCountByStatuses([ReportStatus::RESOLVED]);

        $this->ajaxSendResponse(['all' => $all, 'open' => $open, 'closed' => $closed]);
    }

    private function createSuggestionCategoriesWidget() {
        $code = '
            <div style="width: 75%"><canvas id="suggestionCategoriesWidgetGraph"></canvas></div>
        ';

        return $code;
    }

    public function actionGetSuggestionCategoriesGraphData() {
        global $app;

        $categories = SuggestionCategory::getAll();

        $labels = [];
        $data = [];
        $colors = [];
        foreach($categories as $k => $v) {
            $labels[] = $v;
            $data[] = $app->suggestionRepository->getSuggestionCountByCategories([$k]);
            $colors[] = SuggestionCategory::getColorByKey($k);
        }
        
        $this->ajaxSendResponse(['labels' => $labels, 'data' => $data, 'colors' => $colors]);
    }
}

?>