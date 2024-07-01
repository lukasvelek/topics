<?php

namespace App\Constants;

class TopicMemberRole {
    public const MEMBER = 1;
    public const VIP = 2;
    public const COMMUNITY_HELPER = 3;
    public const MANAGER = 4;
    public const OWNER = 5;

    public static function toString(int $key) {
        return match($key) {
            self::MEMBER => 'Member',
            self::VIP => 'VIP',
            self::COMMUNITY_HELPER => 'Community helper',
            self::MANAGER => 'Manager',
            self::OWNER => 'Owner'
        };
    }

    public static function getColorByKey(int $key) {
        return match($key) {
            self::MEMBER => '#000000',
            self::VIP => '#ffd700',
            self::COMMUNITY_HELPER => '#00cc00',
            self::MANAGER => '#ff0000',
            self::OWNER => '#0000ff'
        };
    }

    public static function getAll() {
        return [
            self::MEMBER => self::toString(self::MEMBER),
            self::VIP => self::toString(self::VIP),
            self::COMMUNITY_HELPER => self::toString(self::COMMUNITY_HELPER),
            self::MANAGER => self::toString(self::MANAGER),
            self::OWNER => self::toString(self::OWNER)
        ];
    }
}

?>