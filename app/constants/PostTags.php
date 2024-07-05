<?php

namespace App\Constants;

class PostTags {
    public const DISCUSSION = 'discussion';
    public const HELP_NEEDED = 'help_needed';

    public static function toString(string $key) {
        return match($key) {
            self::DISCUSSION => 'Discussion',
            self::HELP_NEEDED => 'Help needed'
        };
    }

    public static function getAll() {
        return [
            self::DISCUSSION => self::toString(self::DISCUSSION),
            self::HELP_NEEDED => self::toString(self::HELP_NEEDED)
        ];
    }
}

?>