<?php

namespace App\Constants;

class ReportEntityType extends AConstant {
    public const TOPIC = 1;
    public const POST = 2;
    public const COMMENT = 3;
    public const USER = 4;

    public static function toString($key): string {
        return match((int)$key) {
            self::TOPIC => 'Topic',
            self::POST => 'Post',
            self::COMMENT => 'Comment',
            self::USER => 'User'
        };
    }

    public static function getAll(): array {
        return [
            self::TOPIC => self::toString(self::TOPIC),
            self::POST => self::toString(self::POST),
            self::COMMENT => self::toString(self::COMMENT),
            self::USER => self::toString(self::USER)
        ];
    }
}

?>