<?php

namespace App\Components\Navbar;

class NavbarLinks {
    public const HOME = ['page' => 'UserModule:Home', 'action' => 'dashboard'];
    public const USER_PROFILE = ['page' => 'UserModule:Users', 'action' => 'profile'];
    public const USER_LOGOUT = ['page' => 'UserModule:Logout', 'action' => 'logout'];

    public static function toArray() {
        return [
            'home' => self::HOME
        ];
    }
}

?>