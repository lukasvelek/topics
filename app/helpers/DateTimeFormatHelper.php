<?php

namespace App\Helpers;

use App\Core\Datetypes\DateTime;
use App\Core\Datetypes\TimeTaken;
use DateTime as GlobalDateTime;

class DateTimeFormatHelper {
    public const EUROPEAN_FORMAT = 'd.m.Y H:i';
    public const AMERICAN_FORMAT = 'Y/m/d H:i';
    public const ATOM_FORMAT = GlobalDateTime::ATOM;
    public const TIME_ONLY_FORMAT = 'H:i';

    public static function formatDateToUserFriendly(?string $date, string $format = self::EUROPEAN_FORMAT) {
        if($date === null) {
            return null;
        }
        $date = new DateTime(strtotime($date));
        $date->format($format);
        return $date->getResult();
    }

    public static function formatSecondsToUserFriendly(int $seconds, string $format = 'dHis') {
        $tt = TimeTaken::createFromSeconds($seconds);

        if(!str_contains($format, 'd')) {
            $tt->hideDays();
        }
        if(!str_contains($format, 'H')) {
            $tt->hideHours();
        }
        if(!str_contains($format, 'i')) {
            $tt->hideMinutes();
        }
        if(!str_contains($format, 's')) {
            $tt->hideSeconds();
        }

        return $tt->toString();
    }
}

?>