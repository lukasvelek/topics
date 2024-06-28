<?php

namespace App\Helpers;

use App\Core\Datetypes\DateTime;

class DateTimeFormatHelper {
    public const EUROPEAN_FORMAT = 'd.m.Y H:i';
    public const AMERICAN_FORMAT = 'Y/m/d H:i';

    public static function formatDateToUserFriendly(?string $date, string $format = self::EUROPEAN_FORMAT) {
        if($date === null) {
            return null;
        }
        $date = new DateTime(strtotime($date));
        $date->format($format);
        return $date->getResult();
    }
}

?>