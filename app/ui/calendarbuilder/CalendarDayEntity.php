<?php

namespace App\UI\CalendarBuilder;

class CalendarDayEntity {
    private string $header;
    private array $events;

    public function __construct(string $header, array $events = []) {
        $this->header = $header;
        $this->events = $events;
    }

    public function isEmpty() {
        return $this->header == '';
    }

    public function render() {
        $code = '<b>' . $this->header . '</b>';

        return $code;
    }
}

?>