<?php

namespace App\Constants;

class AdministratorGroups extends AConstant {
    public const G_SUGGESTION_ADMINISTRATOR = 1;
    public const G_REPORT_USER_PROSECUTION_ADMINISTRATOR = 2;
    public const G_SYSTEM_ADMINISTRATOR = 3;
    public const G_USER_ADMINISTRATOR = 4;
    public const G_CONTENT_MANAGER_AND_ADMINISTRATOR = 5;
    public const G_BETA_TESTER = 6;
    public const G_SUPERADMINISTRATOR = 100;

    public static function toString($key): string {
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

    public static function getAll(): array {
        return [
            self::G_SUGGESTION_ADMINISTRATOR => self::toString(self::G_SUGGESTION_ADMINISTRATOR),
            self::G_REPORT_USER_PROSECUTION_ADMINISTRATOR => self::toString(self::G_REPORT_USER_PROSECUTION_ADMINISTRATOR),
            self::G_SYSTEM_ADMINISTRATOR => self::toString(self::G_SYSTEM_ADMINISTRATOR),
            self::G_CONTENT_MANAGER_AND_ADMINISTRATOR => self::toString(self::G_CONTENT_MANAGER_AND_ADMINISTRATOR),
            self::G_BETA_TESTER => self::toString(self::G_BETA_TESTER),
            self::G_SUPERADMINISTRATOR => self::toString(self::G_SUPERADMINISTRATOR)
        ];
    }
}

?>