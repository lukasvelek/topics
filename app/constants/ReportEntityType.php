<?php

namespace App\Constants;

class ReportEntityType implements IToStringConstant {
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
}

?>