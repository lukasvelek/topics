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
    private bool $isToday;

    /**
     * Class constructor
     * 
     * @param string $header Header (day number or empty string)
     * @param array $events Array of events for the given day
     */
    public function __construct(string $header, array $events = []) {
        $this->header = $header;
        $this->events = $events;
        $this->isToday = false;
    }

    /**
     * Sets if the CalendarDayEntity is today
     * 
     * @param bool $isToday True if it is today or false if not
     */
    public function setIsToday(bool $isToday = true) {
        $this->isToday = $isToday;
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
        $code = '<div class="row"><div class="col-md">';

        if($this->isToday) {
            $code .= '<b style="color: red">' . $this->header . '</b>';
        } else {
            $code .= '<b>' . $this->header . '</b>';
        }

        $code .= '</div></div>';
        $code .= '<hr>';
        $code .= '<div class="row"><div class="col-md">';

        if(!empty($this->events)) {
            $code .= implode('<br>', $this->events);
        }

        $code .= '</div></div>';

        return $code;
    }
}

?>