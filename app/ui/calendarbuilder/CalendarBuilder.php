<?php

namespace App\UI\CalendarBuilder;

use App\Core\Datetypes\DateTime;
use App\UI\GridBuilder\GridBuilder;
use App\UI\IRenderable;
use App\UI\LinkBuilder;

/**
 * Calendar Builder UI Library
 * 
 * @author Lukas Velek
 */
class CalendarBuilder implements IRenderable {
    private GridBuilder $grid;
    private int $year;
    private int $month;
    private array $events;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->grid = new GridBuilder();

        $this->year = date('Y');
        $this->month = date('m');

        $this->events = [];
    }

    /**
     * Sets the calendar year
     * 
     * @param int $year
     */
    public function setYear(int $year) {
        $this->year = $year;
    }

    /**
     * Sets the calendar month
     * 
     * @param int $month
     */
    public function setMonth(int $month) {
        $this->month = $month;
    }

    /**
     * Renders the calendar
     * 
     * @return string HTML code
     */
    public function render() {
        $this->preRender();

        return $this->grid->build();
    }

    /**
     * Prerenders the calendar
     * 
     * Creates the calendar header and the calendar days themselves
     */
    private function preRender() {
        $this->createHeader();
        $this->createCalendar();
    }

    /**
     * Creates the days header
     */
    private function createHeader() {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $realDays = [];
        foreach($days as $d) {
            $realDays[$d] = ucfirst($d);
        }

        $this->grid->addColumns($realDays);
    }

    /**
     * Creates the calendar content - day numbers and events
     */
    private function createCalendar() {
        $firstDay = $this->getFirstDayInMonth();
        $lastDay = cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year);

        $dayCells = [];
        $weekCells = [];

        if($firstDay > 0) {
            for($i = 0; $i <= $firstDay; $i++) {
                $dayCells[] = $this->createCell();
            }
        }

        $startingDay = $firstDay;
        $days = 0;
        $weeks = 1;
        for(;;) {
            for($day = $startingDay; $day <= 6; $day++) {
                if($days == $lastDay) {
                    break;
                }
                $dayCells[] = $this->createDayCell(($days + 1));
                $days++;
            }

            $weekCells[] = $this->createWeekCell($dayCells);
            $dayCells = [];

            if($days == $lastDay) {
                break;
            }

            $weeks++;
            $startingDay = 0;
        }

        $this->grid->addDataSourceCallback(function() use ($weekCells) {
            $calendar = [];

            $wc = new class() {
                public string $monday;
                public string $tuesday;
                public string $wednesday;
                public string $thursday;
                public string $friday;
                public string $saturday;
                public string $sunday;

                public function __construct() {
                    $this->monday = '';
                    $this->tuesday = '';
                    $this->wednesday = '';
                    $this->thursday = '';
                    $this->friday = '';
                    $this->saturday = '';
                    $this->sunday = '';
                }
            };

            $ti = 0;
            foreach($weekCells as $week) {
                $w = new $wc();

                $i = 0;
                foreach($week->getDays() as $day) {
                    $dayName = $this->getDayName(($ti + 1));
                    $d = strtolower($dayName);
                    $content = $day->render();
                    $w->$d = $content;
                    $wcs[] = $w;
                    
                    if($i == 6) {
                        $i = 0;
                    } else {
                        $i++;
                    }

                    if(!$day->isEmpty()) {
                        $ti++;
                    }
                }

                $calendar[] = $w;
            }

            return $calendar;
        });
    }

    /**
     * Creates a week cell (table row)
     * 
     * @param array $days Array of CalendarDayEntity instances
     * @return CalendarWeekEntity Week cell (table row)
     */
    private function createWeekCell(array $days) {
        return new CalendarWeekEntity($days);
    }

    /**
     * Creates a day cell (single table cell)
     * 
     * @param string $day Day number
     * @param array $events Event array for the day
     * @return CalendarDayEntity Day cell (single table cell)
     */
    private function createDayCell(string $day, array $events = []) {
        if(empty($events)) {
            if($this->month < 10) {
                $month = '0' . $this->month;
            } else {
                $month = $this->month;
            }
    
            if($day < 10) {
                $day = '0' . $day;
            }
    
            $date = $this->year . '-' . $month . '-' . $day;

            
            if(isset($this->events[$date])) {
                $events = $this->events[$date];
            }
        }

        return $this->createCell($day, $events);
    }

    /**
     * Creates a general day cell. It can be empty but also have a day number.
     * 
     * @param ?string $day Day number or null
     * @param array $events Event array for the day
     * @return CalendarDayEntity Day cell (single table cell)
     */
    private function createCell(?string $day = null, array $events = []) {
        if($day !== null) {
            return new CalendarDayEntity($day, $events);
        } else {
            return new CalendarDayEntity('');
        }
    }

    /**
     * Returns the index of the first day in month (e.g. monday = 0, tuesday = 1, ..., sunday = 6)
     * 
     * @return int Index of the first day in month
     */
    private function getFirstDayInMonth() {
        $day = $this->getDayName(1);
        
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

    /**
     * Returns name of the day
     * 
     * @param int $day Day number
     * @return string Name of the day
     */
    private function getDayName(int $day) {
        if($day < 10) {
            $day = '0' . $day;
        }
        if($this->month < 10) {
            $month = '0' . $this->month;
        } else {
            $month = $this->month;
        }

        $t = $this->year . '-' . $month . '-' . $day . ' 00:00:00';

        $date = new DateTime(strtotime($t));
        $date->format('l');
        return $date->getResult();
    }

    /**
     * Adds PostEntities to the calendar as events
     * 
     * @param array<\App\Entities\PostEntity> $postEntities
     */
    public function addEventsFromPosts(array $postEntities) {
        foreach($postEntities as $pe) {
            $date = new DateTime(strtotime($pe->getDateAvailable()));

            $cee = new CalendarEventEntity($pe->getTitle(), LinkBuilder::createSimpleLink($pe->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $pe->getId()], 'grid-link'), $date);

            $d = $date;
            $d->format('Y-m-d');

            $this->events[$d->getResult()][] = $cee->render();
        }
    }

    public function createCalendarControls(string $jsHandlerName, array $params = []) {
        $buttons = [];

        $backParams = $params;
        $monthName = $this->getMonthName($this->month);

        if($this->month == '01') {
            $backParams[] = $this->year - 1;
            $backParams[] = '12';

            $monthName = $this->getMonthName(12) . ' ' . ($this->year - 1);
        } else {
            $backParams[] = $this->year;
            $backParams[] = $this->month - 1;

            $monthName = $this->getMonthName($this->month - 1);
        }

        $backButton = '<button type="button" class="calendar-control-button" onclick="' . $jsHandlerName . '(\'' . implode('\', \'', $backParams);
        $backButton .= '\')">&larr; ' . $monthName . '</button>';

        $buttons[] = $backButton;

        $todayParams = $params;
        $monthName = $this->getMonthName(date('m'));

        $todayParams[] = date('Y');
        $todayParams[] = date('m');

        $todayButton = '<button type="button" class="calendar-control-button" onclick="' . $jsHandlerName . '(\'' . implode('\', \'', $todayParams);
        $todayButton .= '\')">' . $monthName . '</button>';

        $buttons[] = $todayButton;

        $nextParams = $params;
        $monthName = $this->getMonthName($this->month);

        if($this->month == '12') {
            $nextParams[] = $this->year + 1;
            $nextParams[] = '1';

            $monthName = $this->getMonthName(1) . ' ' . ($this->year + 1);
        } else {
            $nextParams[] = $this->year;
            $nextParams[] = $this->month + 1;

            $monthName = $this->getMonthName($this->month + 1);
        }

        $nextButton = '<button type="button" class="calendar-control-button" onclick="' . $jsHandlerName . '(\'' . implode('\', \'', $nextParams);
        $nextButton .= '\')">' . $monthName . ' &rarr;</button>';

        $buttons[] = $nextButton;

        return $buttons;
    }

    public function getCalendarHeader() {
        $monthName = $this->getMonthName($this->month);

        $code = '<b>' . $monthName . ' ' . $this->year . '</b>';

        return $code;
    }

    /**
     * Returns name of the month
     * 
     * @param int $day Month number
     * @return string Name of the month
     */
    private function getMonthName(int $month) {
        if($month < 10) {
            $month = '0' . $month;
        }

        $t = $this->year . '-' . $month . '-01 00:00:00';

        $date = new DateTime(strtotime($t));
        $date->format('F');
        return $date->getResult();
    }
}

?>