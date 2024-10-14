<?php

namespace App\Constants;

class PostTags extends AConstant {
    public const DISCUSSION = 'discussion';
    public const HELP_NEEDED = 'help_needed';
    public const SHOWCASE = 'showcase';

    public static function toString($key): string {
        return match((string)$key) {
            self::DISCUSSION => 'Discussion',
            self::HELP_NEEDED => 'Help needed',
            self::SHOWCASE => 'Showcase'
        };
    }

    public static function getAll(): array {
        return [
            self::DISCUSSION => self::toString(self::DISCUSSION),
            self::HELP_NEEDED => self::toString(self::HELP_NEEDED),
            self::SHOWCASE => self::toString(self::SHOWCASE)
        ];
    }

    public static function getColorByKey(string $key) {
        return match($key) {
            self::DISCUSSION => [
                'rgb(105, 35, 45)',
                'rgb(205, 135, 145)',
            ],
            self::HELP_NEEDED => [
                'rgb(93, 45, 196)',
                'rgb(193, 145, 255)'
            ],
            self::SHOWCASE => [
                'rgb(35, 179, 174)',
                'rgb(155, 255, 255)'
            ]
        };
    }

    public static function createTagText(string $text, string $fgColor, string $bgColor, bool $moveLower = true) {
        return '<span style="color: ' . $fgColor . '; background-color: ' . $bgColor . '; border: 1px solid ' . $fgColor . '; border-radius: 10px; padding: 5px; margin-right: 5px;' . ($moveLower ? ' position: relative; top: 0.5em;' : '') . '">' . $text . '</span>';
    }
}

?>