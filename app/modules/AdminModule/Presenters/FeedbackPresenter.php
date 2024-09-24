<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportStatus;
use App\Constants\SuggestionCategory;
use App\Constants\SuggestionStatus;

class FeedbackPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('FeedbackPresenter', 'Feedback');
    }

    public function handleDashboard() {
        $this->addScript('createWidgets();');
    }

    public function renderDashboard() {}

    public function actionGetGraphData() {
        $noDataAvailableMessage = 'No data currently available';

        $resultData = [];

        // suggestions
        $suggestions = $this->app->suggestionRepository->getAllSuggestions();

        if(empty($suggestions)) {
            $resultData['suggestions'] = [
                'error' => $noDataAvailableMessage
            ];
        } else {
            $all = count($suggestions);
            $open = $closed = 0;
            foreach($suggestions as $suggestion) {
                if(in_array($suggestion->getStatus(), [SuggestionStatus::OPEN, SuggestionStatus::MORE_INFORMATION_NEEDED, SuggestionStatus::PLANNED])) {
                    $open++;
                }
                if(in_array($suggestion->getStatus(), [SuggestionStatus::RESOLVED, SuggestionStatus::NOT_PLANNED])) {
                    $closed++;
                }
            }

            if($all == 0 && $open == 0 && $closed == 0) {
                $resultData['suggestions'] = [
                    'error' => $noDataAvailableMessage
                ];
            } else {
                $resultData['suggestions'] = [
                    'all' => $all,
                    'open' => $open,
                    'closed' => $closed
                ];
            }
        }

        // reports
        $reports = $this->app->reportRepository->getAllReports();

        if(empty($reports)) {
            $resultData['reports'] = [
                'error' => $noDataAvailableMessage
            ];
        } else {
            $all = count($reports);
            $open = $closed = 0;
            foreach($reports as $report) {
                if($report->getStatus() == ReportStatus::OPEN) {
                    $open++;
                } else if($report->getStatus() == ReportStatus::RESOLVED) {
                    $closed++;
                }
            }

            if($all == 0 && $open == 0 && $closed == 0) {
                $resultData['reports'] = [
                    'error' => $noDataAvailableMessage
                ];
            } else {
                $resultData['reports'] = [
                    'all' => $all,
                    'open' => $open,
                    'closed' => $closed
                ];
            }
        }

        // suggestion categories
        $categories = SuggestionCategory::getAll();

        $count = [];
        foreach($categories as $category => $v) {
            $count[$category] = 0;
        }

        foreach($suggestions as $suggestion) {
            if(is_numeric($count[$suggestion->getCategory()])) {
                $count[$suggestion->getCategory()] += 1;
            } else {
                $count[$suggestion->getCategory()] = 1;
            }
        }

        $noData = true;
        foreach($categories as $category) {
            if(isset($count[$category]) && $count[$category] > 0) {
                $noData = false;
            }
        }

        if($noData) {
            $resultData['suggestionCategories'] = [
                'error' => $noDataAvailableMessage
            ];
        } else {
            $labels = [];
            $data = [];
            $colors = [];
            foreach($categories as $k => $v) {
                $labels[] = $v;
                $data[] = $count[$k];
                $colors[] = SuggestionCategory::getColorByKey($k);
            }

            $resultData['suggestionCategories'] = [
                'labels' => $labels,
                'data' => $data,
                'colors' => $colors
            ];
        }

        return $resultData;
    }
}

?>