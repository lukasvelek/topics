<?php

namespace App\Components\Navbar;

class NavbarAdminLinks {
    public const DASHBOARD = ['page' => 'AdminModule:Home', 'action' => 'dashboard'];

    public static function toArray() {
        return [
            'dashboard' => self::DASHBOARD
        ];
    }
}

?>