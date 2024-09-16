<?php

namespace App\Constants;

class AdministratorGroups {
    public const G_SUGGESTION_ADMINISTRATOR = 1;
    public const G_REPORT_USER_PROSECUTION_ADMINISTRATOR = 2;
    public const G_SYSTEM_ADMINISTRATOR = 3;
    public const G_USER_ADMINISTRATOR = 4;
    public const G_CONTENT_MANAGER_AND_ADMINISTRATOR = 5;
    public const G_BETA_TESTER = 6;
    public const G_SUPERADMINISTRATOR = 100;

    public static function toString(int $key) {
        return match($key) {
            self::G_SUGGESTION_ADMINISTRATOR => 'Suggestion administrator',
            self::G_REPORT_USER_PROSECUTION_ADMINISTRATOR => 'Report & user prosecution administrator',
            self::G_SYSTEM_ADMINISTRATOR => 'System administrator',
            self::G_SUPERADMINISTRATOR => 'Super administrator',
            self::G_USER_ADMINISTRATOR => 'User administrator',
            self::G_CONTENT_MANAGER_AND_ADMINISTRATOR => 'Content manager & administrator',
            self::G_BETA_TESTER => 'Beta testers'
        };
    }
}

?>