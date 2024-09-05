<?php

namespace App\UI\CalendarBuilder;

use App\Core\Datetypes\DateTime;
use App\UI\GridBuilder\GridBuilder;
use App\UI\IRenderable;

class CalendarBuilder implements IRenderable {
    private GridBuilder $grid;
    private int $year;
    private int $month;

    public function __construct() {
        $this->grid = new GridBuilder();

        $this->year = date('Y');
        $this->month = date('m');
    }

    public function setYear(int $year) {
        $this->year = $year;
    }

    public function setMonth(int $month) {
        $this->month = $month;
    }

    public function render() {
        $this->preRender();

        return $this->grid->build();
    }

    private function preRender() {
        $this->createHeader();
    }

    private function createHeader() {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $realDays = [];
        foreach($days as $d) {
            $realDays[$d] = ucfirst($d);
        }

        $this->grid->addColumns($realDays);
    }

    private function createCalendar() {
        $firstDay = $this->getFirstDayInMonth();
    }

    private function getFirstDayInMonth() {
        $date = $this->year . '-' . $this->month . '-01 00:00:00';
        $day = new DateTime($date);
        $day->format('l');
        $day = $day->getResult();
        
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        $i = 0;
        foreach($days as $d) {
            if($d == strtolower($day)) {
                break;
            }

            $i++;
        }

        return $i;
    }
}

?>