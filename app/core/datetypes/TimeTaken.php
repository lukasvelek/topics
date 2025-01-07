<?php

namespace App\Core\Datetypes;

use App\Exceptions\KeyInArrayDoesNotExistException;
use App\Exceptions\TypeException;
use App\Helpers\ArrayHelper;
use App\Helpers\ValueHelper;

/**
 * This class contains information about how long did an action take
 * 
 * @author Lukas Velek
 */
class TimeTaken {
    private int $seconds;
    private int $minutes;
    private int $hours;
    private int $days;
    private array $toHide;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->seconds = 0;
        $this->minutes = 0;
        $this->hours = 0;
        $this->days = 0;
        $this->toHide = [];
    }

    /**
     * Hides days
     */
    public function hideDays() {
        $this->toHide[] = 'days';
    }

    /**
     * Hides hours
     */
    public function hideHours() {
        $this->toHide[] = 'hours';
    }

    /**
     * Hides minutes
     */
    public function hideMinutes() {
        $this->toHide[] = 'minutes';
    }

    /**
     * Hides seconds
     */
    public function hideSeconds() {
        $this->toHide[] = 'seconds';
    }

    /**
     * Converts self to string and returns it
     * 
     * @return string To string representation of self
     */
    public function toString() {
        $parts = [];

        if($this->days > 0 && !in_array('days', $this->toHide)) {
            $parts[] = $this->days . ' days';
        }

        if($this->hours > 0 && !in_array('hours', $this->toHide)) {
            $parts[] = $this->hours . 'h';
        }

        if($this->minutes > 0 && !in_array('minutes', $this->toHide)) {
            $parts[] = $this->minutes . 'm';
        }

        if(!in_array('seconds', $this->toHide)) {
            $parts[] = $this->seconds . 's';
        }

        return implode(' ', $parts);
    }

    /**
     * Sets values from array
     * 
     * @param array $data Data array
     */
    public function setFromArray(array $data) {
        if(!ArrayHelper::checkArrayKeysExistInArray(['seconds'], $data)) {
            throw new KeyInArrayDoesNotExistException('seconds', '$data');
        } else if(array_key_exists('seconds', $data) && !ArrayHelper::checkArrayKeysExistInArray(['minutes', 'hours', 'days'], $data)) {
            if(ValueHelper::isValueInteger($data['seconds'])) {
                $this->recalculateFromSeconds($data['seconds']);
            } else {
                throw new TypeException('integer', '$data["seconds"]', $data['seconds']);
            }
        } else {
            if(ValueHelper::isValueInteger($data['seconds'])) {
                $this->seconds = $data['seconds'];
            } else {
                throw new TypeException('integer', '$data["seconds"]', $data['seconds']);
            }

            if(ValueHelper::isValueInteger($data['minutes'])) {
                $this->seconds = $data['minutes'];
            } else {
                throw new TypeException('integer', '$data["minutes"]', $data['minutes']);
            }

            if(ValueHelper::isValueInteger($data['hours'])) {
                $this->seconds = $data['hours'];
            } else {
                throw new TypeException('integer', '$data["hours"]', $data['hours']);
            }

            if(ValueHelper::isValueInteger($data['days'])) {
                $this->days = $data['days'];
            } else {
                throw new TypeException('integer', '$data["days"]', $data['days']);
            }
        }
    }

    /**
     * Calculates time from seconds
     * 
     * @param int $seconds Seconds
     */
    private function recalculateFromSeconds(int $seconds) {
        while($seconds >= 60) {
            $seconds -= 60;
            $this->minutes++;
        }

        while($this->minutes >= 60) {
            $this->minutes -= 60;
            $this->hours++;
        }

        while($this->hours >= 24) {
            $this->hours -= 24;
            $this->days++;
        }

        $this->seconds = $seconds;
    }

    /**
     * Creates a TimeTaken instance from seconds
     * 
     * @param int $seconds Seconds
     * @return self
     */
    public static function createFromSeconds(int $seconds) {
        $obj = new self();
        $obj->setFromArray(['seconds' => $seconds]);
        return $obj;
    }
}

?>