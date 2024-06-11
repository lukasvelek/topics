<?php

namespace App\Constants;

class SuggestionCategory {
    public const BUG = 'bug';
    public const SECURITY = 'security';
    public const FUNCTION_REQUEST = 'functionRequest';

    public static function toString(string $key) {
        return match($key) {
            self::BUG => 'Bug',
            self::SECURITY => 'Security',
            self::FUNCTION_REQUEST => 'Function request'
        };
    }

    public static function getAll() {
        return [
            self::BUG => self::toString(self::BUG),
            self::SECURITY => self::toString(self::SECURITY),
            self::FUNCTION_REQUEST => self::toString(self::FUNCTION_REQUEST)
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
}

?>