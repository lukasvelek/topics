<?php

namespace App\Modules\UserModule;

use App\Core\AjaxRequestBuilder;
use App\UI\CalendarBuilder\CalendarBuilder;
use App\UI\LinkBuilder;

class TopicCalendarPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicCalendarPresenter', 'Topic calendar');
    }

    public function handleCalendar() {
        $topicId = $this->httpGet('topicId', true);

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setHeader(['topicId' => '_topicId', 'year' => '_year', 'month' => '_month'])
            ->setAction($this, 'getCalendar')
            ->setFunctionName('getCalendar')
            ->setFunctionArguments(['_topicId', '_year', '_month'])
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-controls', 'controls')
            ->updateHTMLElement('calendar-info', 'info')
        ;

        $this->addScript($arb);
        $this->addScript('getCalendar(\'' . $topicId . '\', ' . date('Y') . ', ' . date('m') . ')');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderCalendar() {
        $this->template->grid_controls = $this->loadFromPresenterCache('gridControls');
        $this->template->links = $this->loadFromPresenterCache('links');
    }

    public function actionGetCalendar() {
        global $app;

        $topicId = $this->httpGet('topicId', true);

        $month = $this->httpGet('month');
        $year = $this->httpGet('year');

        $calendar = new CalendarBuilder();
        $calendar->setYear($year);
        $calendar->setMonth($month);

        // Scheduled posts
        $dateFrom = $year . '-' . $month . '-01 00:00:00';

        $lastDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dateTo = $year . '-' . $month . '-' . $lastDay . ' 00:00:00';

        $posts = $app->postRepository->getScheduledPostsForTopicIdForDate($dateFrom, $dateTo, $topicId);

        $calendar->addEventsFromPosts($posts);
        // End of scheduled posts

        $this->ajaxSendResponse(['grid' => $calendar->render(), 'controls' => $calendar->createCalendarControls('getCalendar', [$topicId]), 'info' => $calendar->getCalendarHeader()]);
    }
}

?>