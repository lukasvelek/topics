<?php

namespace App\Constants;

class UserProsecutionType extends AConstant {
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

    public static function getAll(): array {
        return [
            self::WARNING => self::toString(self::WARNING),
            self::BAN => self::toString(self::BAN),
            self::PERMA_BAN => self::toString(self::PERMA_BAN)
        ];
    }
}

?>