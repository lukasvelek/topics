<?php

namespace App\Core\Datetypes;

use App\Exceptions\KeyInArrayDoesNotExistException;
use App\Exceptions\TypeException;
use App\Helpers\ArrayHelper;
use App\Helpers\ValueHelper;

class TimeTaken {
    private int $seconds;
    private int $minutes;
    private int $hours;
    private int $days;

    public function __construct() {
        $this->seconds = 0;
        $this->minutes = 0;
        $this->hours = 0;
        $this->days = 0;
    }

    public function toString() {
        $parts = [];

        if($this->days > 0) {
            $parts[] = $this->days . ' days';
        }

        if($this->hours > 0) {
            $parts[] = $this->hours . 'h';
        }

        if($this->minutes > 0) {
            $parts[] = $this->minutes . 'm';
        }

        $parts[] = $this->seconds . 's';

        return implode(' ', $parts);
    }

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

    public static function createFromSeconds(int $seconds) {
        $obj = new self();
        $obj->setFromArray(['seconds' => $seconds]);
        return $obj;
    }
}

?>