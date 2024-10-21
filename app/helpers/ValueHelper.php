<?php

namespace App\Helpers;

/**
 * ValueHelper helps with working with values
 * 
 * @author Lukas Velek
 */
class ValueHelper {
    private const TYPE_INT = 'int';
    private const TYPE_STRING = 'string';
    private const TYPE_DOUBLE = 'double';
    private const TYPE_BOOL = 'bool';
    private const TYPE_NULL = 'null';
    private const TYPE_ARRAY = 'array';

    /**
     * Checks if given value is integer
     * 
     * @param mixed $value Value to check
     * @return bool True if value is integer or false if not
     */
    public static function isValueInteger(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_INT);
    }

    /**
     * Checks if given value is string
     * 
     * @param mixed $value Value to check
     * @return bool True if value is string or false if not
     */
    public static function isValueString(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_STRING);
    }

    /**
     * Checks if given value is double
     * 
     * @param mixed $value Value to check
     * @return bool True if value is double or false if not
     */
    public static function isValueDouble(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_DOUBLE);
    }

    /**
     * Checks if given value is boolean
     * 
     * @param mixed $value Value to check
     * @return bool True if value is boolean or false if not
     */
    public static function isValueBool(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_BOOL);
    }

    /**
     * Checks if given value is null
     * 
     * @param mixed $value Value to check
     * @return bool True if value is null or false if not
     */
    public static function isValueNull(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_NULL);
    }

    /**
     * Checks if given value is array
     * 
     * @param mixed $value Value to check
     * @return bool True if value is array or false if not
     */
    public static function isValueArray(mixed $value) {
        return self::internalCheckValue($value, self::TYPE_ARRAY);
    }

    /**
     * Checks if a value is of a given type
     * 
     * @param mixed $value Value to check
     * @param string $type Type the value has to be of
     * @return bool True if value is of the given type or false if not
     */
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