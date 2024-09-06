<?php

namespace App\UI\CalendarBuilder;

use App\Core\Datetypes\DateTime;
use App\Helpers\ColorHelper;

/**
 * CalendarEventEntity represents an event on a single day
 * 
 * @author Lukas Velek
 */
class CalendarEventEntity {
    private string $name;
    private string $link;
    private DateTime $date;

    /**
     * Class constructor
     * 
     * @param string $name Name of the event
     * @param string $link Link to the event
     * @param DateTime $date Date of the event
     */
    public function __construct(string $name, string $link, DateTime $date) {
        $this->name = $name;
        $this->link = $link;
        $this->date = $date;
    }

    /**
     * Returns the event date
     * 
     * @return DateTime Event date
     */
    public function getDate() {
        return $this->date;
    }

    /**
     * Renders the event to HTML code
     * 
     * @return string HTML code
     */
    public function render() {
        $bgColor = $this->generateRandomColor();

        $code = '<span style="background-color: ' . $bgColor . '; border-radius: 10px; padding: 2px">' . $this->link . '</span>';

        return $code;
    }

    /**
     * Generates random color for the background
     * 
     * @return string Random color
     */
    private function generateRandomColor() {
        [$text, $bg] = ColorHelper::createColorCombination();

        return $bg;
    }
}

?>