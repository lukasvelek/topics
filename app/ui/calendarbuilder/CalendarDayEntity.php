<?php

namespace App\UI\CalendarBuilder;

/**
 * CalendarDayEntity represents a day in calendar
 * 
 * @author Lukas Velek
 */
class CalendarDayEntity {
    private string $header;
    private array $events;

    /**
     * Class constructor
     * 
     * @param string $header Header (day number or empty string)
     * @param array $events Array of events for the given day
     */
    public function __construct(string $header, array $events = []) {
        $this->header = $header;
        $this->events = $events;
    }

    /**
     * Returns if the day is empty
     * 
     * @return bool True if the day is empty or false if not
     */
    public function isEmpty() {
        return $this->header == '';
    }

    /**
     * Renders the day content
     * 
     * @return string HTML code
     */
    public function render() {
        $code = '<b>' . $this->header . '</b>';

        if(!empty($this->events)) {
            $code .= '<br>';
            $code .= implode('<br>', $this->events);
        }

        return $code;
    }
}

?>