<?php

namespace App\Constants;

class ReportCategory extends AConstant {
    public const HARMFUL_CONTENT = 1;
    public const NUDITY = 2;
    public const SEXUAL_HARASSMENT = 3;
    public const INAPPROPRIATE_CONTENT = 4;
    public const OTHER = 5;

    public static function toString($key): string {
        return match((int)$key) {
            self::HARMFUL_CONTENT => 'Harmful content',
            self::NUDITY => 'Nudity',
            self::SEXUAL_HARASSMENT => 'Sexual harassment',
            self::INAPPROPRIATE_CONTENT => 'Inappropriate content',
            self::OTHER => 'Other'
        };
    }

    public static function getAll(): array {
        return [
            self::HARMFUL_CONTENT => self::toString(self::HARMFUL_CONTENT),
            self::NUDITY => self::toString(self::NUDITY),
            self::SEXUAL_HARASSMENT => self::toString(self::SEXUAL_HARASSMENT),
            self::INAPPROPRIATE_CONTENT => self::toString(self::INAPPROPRIATE_CONTENT),
            self::OTHER => self::toString(self::OTHER)
        ];
    }
}

?>