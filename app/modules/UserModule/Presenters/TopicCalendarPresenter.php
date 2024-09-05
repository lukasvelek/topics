<?php

namespace App\Modules\UserModule;

use App\UI\CalendarBuilder\CalendarBuilder;

class TopicCalendarPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicCalendarPresenter', 'Topic calendar');
    }

    public function handleCalendar() {
        $calendar = new CalendarBuilder();

        $this->saveToPresenterCache('calendar', $calendar);
    }

    public function renderCalendar() {
        $this->template->grid_content = $this->loadFromPresenterCache('calendar');
    }
}

?>