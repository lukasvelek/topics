<?php

namespace App\Constants;

class TopicMemberRole {
    public const MEMBER = 1;
    public const VIP = 2;
    public const COMMUNITY_HELPER = 3;
    public const OWNER = 4;

    public static function toString(int $key) {
        return match($key) {
            self::MEMBER => 'Member',
            self::VIP => 'VIP',
            self::COMMUNITY_HELPER => 'Community helper',
            self::OWNER => 'Owner'
        };
    }
}

?>