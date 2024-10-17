<?php

namespace App\Constants;

class Systems extends AConstant {
    public const CORE = 'core';
    public const SYSTEM_SERVICES = 'systemServices';
    
    public static function getAll(): array {
        return [
            self::CORE => self::toString(self::CORE),
            self::SYSTEM_SERVICES => self::toString(self::SYSTEM_SERVICES)
        ];
    }

    public static function toString($key): string {
        return match((string)$key) {
            self::CORE => 'Core',
            self::SYSTEM_SERVICES => 'System services'
        };
    }
}

?>