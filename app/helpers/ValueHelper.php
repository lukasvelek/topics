<?php

namespace App\Helpers;

class ValueHelper {
    private const TYPE_INT = 'int';
    private const TYPE_STRING = 'string';
    private const TYPE_DOUBLE = 'double';
    private const TYPE_BOOL = 'bool';
    private const TYPE_NULL = 'null';
    private const TYPE_ARRAY = 'array';

    public static function isValueInteger(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_INT);
    }

    public static function isValueString(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_STRING);
    }

    public static function isValueDouble(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_DOUBLE);
    }

    public static function isValueBool(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_BOOL);
    }

    public static function isValueNull(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_NULL);
    }

    public static function isValueArray(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_ARRAY);
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

            case self::TYPE_BOOL:
                if(is_bool($value) || (self::internalCheckValue($value, self::TYPE_INT))) {
                    return true;
                }
                break;
            
            case self::TYPE_NULL:
                if(is_null($value) && ($value === null)) {
                    return true;
                }
                break;

            case self::TYPE_ARRAY:
                if(is_array($value)) {
                    return true;
                }
                break;
        }

        return false;
    }
}

?>