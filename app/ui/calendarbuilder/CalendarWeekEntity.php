<?php

namespace App\UI\CalendarBuilder;

class CalendarWeekEntity {
    private array $days;

    public function __construct(array $days) {
        $this->days = $days;
    }

    public function getDay(int $index) {
        return $this->days[$index];
    }

    public function getDays() {
        return $this->days;
    }
}

?>