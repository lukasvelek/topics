<?php

namespace App\Constants;

class SystemServiceStatus extends AConstant {
    public const NOT_RUNNING = 1;
    public const RUNNING = 2;

    public static function toString($key): string {
        return match((int)$key) {
            self::NOT_RUNNING => 'Not running',
            self::RUNNING => 'Running'
        };
    }

    public static function getAll(): array {
        return [
            self::NOT_RUNNING => self::toString(self::NOT_RUNNING),
            self::RUNNING => self::toString(self::RUNNING)
        ];
    }
}

?>