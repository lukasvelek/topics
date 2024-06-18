<?php

namespace App\Components\Navbar;

class NavbarLinks {
    public const HOME = ['page' => 'UserModule:Home', 'action' => 'dashboard'];
    public const FOLLOWED_TOPICS = ['page' => 'UserModule:Topics', 'action' => 'followed'];
    public const DISCOVER_TOPICS = ['page' => 'UserModule:Topics', 'action' => 'discover'];
    public const USER_PROFILE = ['page' => 'UserModule:Users', 'action' => 'profile'];
    public const USER_LOGOUT = ['page' => 'UserModule:Logout', 'action' => 'logout'];
    public const ADMINISTRATION = ['page' => 'AdminModule:Home', 'action' => 'dashboard'];

    public static function toArray() {
        return [
            'home' => self::HOME,
            'followed' => self::FOLLOWED_TOPICS,
            'discover' => self::DISCOVER_TOPICS
        ];
    }
}

?>