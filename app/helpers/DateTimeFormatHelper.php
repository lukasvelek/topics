<?php

namespace App\Helpers;

class DateTimeFormatHelper {
    public const EUROPEAN_FORMAT = 'd.m.Y H:i';
    public const AMERICAN_FORMAT = 'Y/m/d H:i';

    public static function formatDateToUserFriendly(string $date, string $format = self::EUROPEAN_FORMAT) {
        return date($format, strtotime($date));
    }
}

?>