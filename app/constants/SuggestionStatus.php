<?php

namespace App\Constants;

class SuggestionStatus {
    public const OPEN = 1;
    public const RESOLVED = 2;
    public const MORE_INFORMATION_NEEDED = 3;
    public const NOT_PLANNED = 4;
    public const PLANNED = 5;

    public static function toString(int $status) {
        return match($status) {
            self::OPEN => 'Open',
            self::RESOLVED => 'Resolved',
            self::MORE_INFORMATION_NEEDED => 'More information needed',
            self::NOT_PLANNED => 'Not planned',
            self::PLANNED => 'Planned'
        };
    }

    public static function getColorByStatus(int $status) {
        return match($status) {
            self::OPEN => '#0000bb',
            self::RESOLVED => '#00bb00',
            self::MORE_INFORMATION_NEEDED => '#bb00bb',
            self::NOT_PLANNED => '#222222',
            self::PLANNED => '#bb0000'
        };
    }
}

?>