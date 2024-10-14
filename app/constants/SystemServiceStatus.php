<?php

namespace App\Constants;

class SystemServiceStatus implements IToStringConstant {
    public const NOT_RUNNING = 1;
    public const RUNNING = 2;

    public static function toString($key): string {
        return match((int)$key) {
            self::NOT_RUNNING => 'Not running',
            self::RUNNING => 'Running'
        };
    }
}

?>