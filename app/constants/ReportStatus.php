<?php

namespace App\Constants;

class ReportStatus {
    public const OPEN = 1;
    public const RESOLVED = 2;

    public static function toString(int $key) {
        return match($key) {
            self::OPEN => 'Open',
            self::RESOLVED => 'Resolved'
        };
    }
}

?>