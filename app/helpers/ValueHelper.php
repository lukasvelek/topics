<?php

namespace App\Helpers;

class ValueHelper {
    private const TYPE_INT = 'int';
    private const TYPE_STRING = 'string';
    private const TYPE_DOUBLE = 'double';

    public static function isValueInteger(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_INT);
    }

    private static function internalCheckValue(mixed $value, string $type) {
        switch($type) {
            case self::TYPE_DOUBLE:
                if(is_double($value) || is_float($value)) {
                    return true;
                }
                break;

            case self::TYPE_INT:
                if(is_int($value) || is_integer($value) || is_numeric($value)) {
                    return true;
                }
                break;

            case self::TYPE_STRING:
                if(is_string($value)) {
                    return true;
                }
                break;
        }

        return false;
    }
}

?>