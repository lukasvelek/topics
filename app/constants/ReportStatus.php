<?php

namespace App\Constants;

class ReportStatus extends AConstant {
    public const OPEN = 1;
    public const RESOLVED = 2;

    public static function toString($key): string {
        return match((int)$key) {
            self::OPEN => 'Open',
            self::RESOLVED => 'Resolved'
        };
    }

    public static function getAll(): array {
        return [
            self::OPEN => self::toString(self::OPEN),
            self::RESOLVED => self::toString(self::RESOLVED)
        ];
    }
}

?>