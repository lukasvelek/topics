<?php

namespace App\Constants;

class SystemServiceStatus {
    public const NOT_RUNNING = 1;
    public const RUNNING = 2;

    public static function toString(int $key) {
        return match($key) {
            self::NOT_RUNNING => 'Not running',
            self::RUNNING => 'Running'
        };
    }
}

?>