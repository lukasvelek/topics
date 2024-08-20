<?php

namespace App\Constants;

class SystemStatus {
    public const ONLINE = 1;
    public const OFFLINE = 2;
    public const MAINTENANCE = 3;
    public const ISSUES_REPORTED = 4;

    public static function toString(int $code) {
        return match($code) {
            self::ONLINE => 'Online',
            self::OFFLINE => 'Offline',
            self::MAINTENANCE => 'Ongoing maintenance',
            self::ISSUES_REPORTED => 'Issues reported'
        };
    }

    public static function getColorByCode(int $code) {
        return match($code) {
            self::ONLINE => '#009900',
            self::OFFLINE => '#aaaaaa',
            self::MAINTENANCE => '#bb0000',
            self::ISSUES_REPORTED => '#ffa500'
        };
    }

    public static function getAll() {
        return [
            self::ONLINE => self::toString(self::ONLINE),
            self::OFFLINE => self::toString(self::OFFLINE),
            self::MAINTENANCE => self::toString(self::MAINTENANCE),
            self::ISSUES_REPORTED => self::toString(self::ISSUES_REPORTED)
        ];
    }
}

?>