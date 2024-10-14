<?php

namespace App\Constants;

class UserProsecutionType implements IToStringConstant {
    public const WARNING = 1;
    public const BAN = 2;
    public const PERMA_BAN = 3;

    public static function toString($key): string {
        return match((int)$key) {
            self::WARNING => 'Warning',
            self::BAN => 'Ban',
            self::PERMA_BAN => 'Permanent ban'
        };
    }
}

?>