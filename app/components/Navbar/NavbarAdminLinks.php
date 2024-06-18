<?php

namespace App\Components\Navbar;

class NavbarAdminLinks {
    public const LEAVE_ADMINISTRATION = ['page' => 'UserModule:Home', 'action' => 'dashboard'];
    public const DASHBOARD = ['page' => 'AdminModule:Home', 'action' => 'dashboard'];
    public const FEEDBACK = ['page' => 'AdminModule:Feedback', 'action' => 'dashboard'];
    public const MANAGE = ['page' => 'AdminModule:Manage', 'action' => 'dashboard'];

    public static function toArray() {
        return [
            'leave administration' => self::LEAVE_ADMINISTRATION,
            'dashboard' => self::DASHBOARD,
            'feedback' => self::FEEDBACK,
            'manage' => self::MANAGE
        ];
    }
}

?>