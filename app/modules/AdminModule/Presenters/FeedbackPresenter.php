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
        $this->addScript('createWidgets();');
    }

    public function renderDashboard() {}

    public function actionGetGraphData() {
        global $app;

        $resultData = [];

        // suggestions
        $all = $app->suggestionRepository->getSuggestionCountByStatuses();
        $open = $app->suggestionRepository->getOpenSuggestionCount();
        $closed = $app->suggestionRepository->getSuggestionCountByStatuses([SuggestionStatus::RESOLVED, SuggestionStatus::NOT_PLANNED]);
        
        $resultData['suggestions'] = [
            'all' => $all,
            'open' => $open,
            'closed' => $closed
        ];

        // reports
        $all = $app->reportRepository->getReportCountByStatuses();
        $open = $app->reportRepository->getReportCountByStatuses([ReportStatus::OPEN]);
        $closed = $app->reportRepository->getReportCountByStatuses([ReportStatus::RESOLVED]);

        $resultData['reports'] = [
            'all' => $all,
            'open' => $open,
            'closed' => $closed
        ];

        // suggestion categories
        $categories = SuggestionCategory::getAll();

        $labels = [];
        $data = [];
        $colors = [];
        foreach($categories as $k => $v) {
            $labels[] = $v;
            $data[] = $app->suggestionRepository->getSuggestionCountByCategories([$k]);
            $colors[] = SuggestionCategory::getColorByKey($k);
        }

        $resultData['suggestionCategories'] = [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors
        ];

        $this->ajaxSendResponse($resultData);
    }
}

?>