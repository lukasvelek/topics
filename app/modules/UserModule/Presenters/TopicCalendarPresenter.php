<?php

namespace App\Modules\UserModule;

use App\UI\CalendarBuilder\CalendarBuilder;
use App\UI\LinkBuilder;

class TopicCalendarPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicCalendarPresenter', 'Topic calendar');
    }

    public function handleCalendar() {
        $topicId = $this->httpGet('topicId', true);

        $calendar = new CalendarBuilder();

        $this->saveToPresenterCache('calendar', $calendar);

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId], 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);
    }

    public function renderCalendar() {
        $this->template->grid_content = $this->loadFromPresenterCache('calendar');
        $this->template->links = $this->loadFromPresenterCache('links');
    }
}

?>