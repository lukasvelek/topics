<?php

namespace App\UI\CalendarBuilder;

/**
 * CalendarWeekEntity represents a week in calendar
 * 
 * @author Lukas Velek
 */
class CalendarWeekEntity {
    private array $days;

    /**
     * Class constructor
     * 
     * @param array $days Array of instances of CalendarDayEntity that represent single days
     */
    public function __construct(array $days) {
        $this->days = $days;
    }

    /**
     * Returns all days
     * 
     * @return array Days
     */
    public function getDays() {
        return $this->days;
    }
}

?>