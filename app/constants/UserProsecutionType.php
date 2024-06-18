<?php

namespace App\Constants;

class UserProsecutionType {
    public const WARNING = 1;
    public const BAN = 2;
    public const PERMA_BAN = 3;

    public static function toString(int $key) {
        return match($key) {
            self::WARNING => 'Warning',
            self::BAN => 'Ban',
            self::PERMA_BAN => 'Permanent ban'
        };
    }
}

?>