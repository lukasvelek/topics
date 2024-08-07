<?php

namespace App\Constants;

class SuggestionCategory {
    public const BUG = 'bug';
    public const SECURITY = 'security';
    public const FUNCTION_REQUEST = 'functionRequest';
    public const OTHER = 'other';
    public const PERFORMANCE = 'Performance';

    public static function toString(string $key) {
        return match($key) {
            self::BUG => 'Bug',
            self::SECURITY => 'Security',
            self::FUNCTION_REQUEST => 'Function request',
            self::PERFORMANCE => 'Performance',
            self::OTHER => 'Other'
        };
    }

    public static function getAll() {
        return [
            self::BUG => self::toString(self::BUG),
            self::SECURITY => self::toString(self::SECURITY),
            self::FUNCTION_REQUEST => self::toString(self::FUNCTION_REQUEST),
            self::PERFORMANCE => self::toString(self::PERFORMANCE),
            self::OTHER => self::toString(self::OTHER)
        ];
    }

    public static function createSelectOptionArray() {
        $tmp = [];

        foreach(self::getAll() as $value => $text) {
            $tmp[] = [
                'text' => $text,
                'value' => $value
            ];
        }

        return $tmp;
    }

    public static function getColorByKey(string $key) {
        return match($key) {
            self::BUG => '#bb00bb',
            self::SECURITY => '#bb0000',
            self::FUNCTION_REQUEST => '#0000bb',
            self::PERFORMANCE => '#bbbb00',
            self::OTHER => '#222222'
        };
    }
}

?>