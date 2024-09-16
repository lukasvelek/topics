<?php

namespace App\Entities;

use App\Core\DB\DatabaseRow;
use App\Exceptions\KeyInArrayDoesNotExistException;
use App\Exceptions\TypeException;
use App\Helpers\ValueHelper;

/**
 * AEntity contains useful methods for entities
 * 
 * @author Lukas Velek
 */
abstract class AEntity implements ICreatableFromRow {
    /**
     * Checks if $value is of type integer
     * 
     * @param mixed $value Value to check
     * @return bool True if $value is integer or false if not
     */
    protected static function checkInt(mixed $value) {
        return ValueHelper::isValueInteger($value);
    }

    /**
     * Checks if $value is of type integer or null
     * 
     * @param mixed $value Value to check
     * @return bool True if $value is integer or null or false if not
     */
    protected static function checkIntOrNull(mixed $value) {
        return ValueHelper::isValueInteger($value) || ValueHelper::isValueNull($value);
    }

    /**
     * Checks if $value is of type string
     * 
     * @param mixed $value Value to check
     * @return bool True if $value is string or false if not
     */
    protected static function checkString(mixed $value) {
        return ValueHelper::isValueString($value);
    }

    /**
     * Checks if $value is of type string or null
     * 
     * @param mixed $value Value to check
     * @return bool True if $value is string or null or false if not
     */
    protected static function checkStringOrNull(mixed $value) {
        return ValueHelper::isValueString($value) || ValueHelper::isValueNull($value);
    }

    /**
     * Checks if $value is of type boolean
     * 
     * @param mixed $value Value to check
     * @return bool True if $value is boolean or false if not
     */
    protected static function checkBool(mixed $value) {
        return ValueHelper::isValueBool($value);
    }

    /**
     * Checks if $value is of type double
     * 
     * @param mixed $value Value to check
     * @return bool True if $value is double or false if not
     */
    protected static function checkDouble(mixed $value) {
        return ValueHelper::isValueDouble($value);
    }

    /**
     * Checks if $value is of type double or null
     * 
     * @param mixed $value Value to check
     * @return bool True if $value is double or null or false if not
     */
    protected static function checkDoubleOrNull(mixed $value) {
        return ValueHelper::isValueDouble($value) || ValueHelper::isValueNull($value);
    }

    /**
     * Checks if $value is of type array
     * 
     * @param mixed $value Value to check
     * @return bool True if $value is array or false if not
     */
    protected static function checkArray(mixed $value) {
        return ValueHelper::isValueArray($value);
    }

    /**
     * Returns the type of $value
     * 
     * Possible results are:
     * 'bool' - $value is of type boolean
     * 'double' - $value is of type double
     * 'int' - $value is of type integer
     * 'string' - $value is of type string
     * 'null' - $value is of type null
     * 'array' - $value is of type array
     * null - could not return value
     * 
     * @param mixed $value Value to check
     * @return string|null One of possible values
     */
    protected static function getValueType(mixed $value) {
        if(self::checkBool($value)) {
            return 'bool';
        }
        if(self::checkDouble($value)) {
            return 'double';
        }
        if(self::checkInt($value)) {
            return 'int';
        }
        if(self::checkString($value)) {
            return 'string';
        }
        if(self::checkArray($value)) {
            return 'array';
        }
        if(ValueHelper::isValueNull($value)) {
            return 'null';
        }
        return null;
    }

    /**
     * Creates a database row instance
     * 
     * @param mixed $row Row from mysqli
     * @return DatabaseRow DatabaseRow instance
     */
    protected static function createRow(mixed $row) {
        if($row instanceof DatabaseRow) {
            return $row;
        }

        $dr = new DatabaseRow();

        foreach($row as $k => $v) {
            $dr->$k = $v;
        }

        return $dr;
    }

    /**
     * Checks for type in DatabaseRow attribute
     * 
     * @param DatabaseRow $row DatabaseRow instance containing all data
     * @param string $name Attribute name
     * @param string $type Expected type
     * @param bool $nullable Is type nullable
     * @return void
     * @throws KeyInArrayDoesNotExistException
     * @throws TypeException
     */
    protected static function checkType(DatabaseRow $row, string $name, string $type, bool $nullable = false) {
        $result = true;
        switch($type) {
            case 'string':
                if($nullable) {
                    $result = self::checkStringOrNull($row->$name);
                } else {
                    $result = self::checkString($row->$name);
                }
                break;

            case 'bool':
                $result = self::checkBool($row->$name);
                break;

            case 'int':
                if($nullable) {
                    $result = self::checkIntOrNull($row->$name);
                } else {
                    $result = self::checkInt($row->$name);
                }
                break;

            case 'double':
                if($nullable) {
                    $result = self::checkDoubleOrNull($row->$name);
                } else {
                    $result = self::checkDouble($row->$name);
                }
                break;

            case 'array':
                $result = self::checkArray($row->$name);
                break;
        }
        if(!$result) {
            throw new TypeException($type, $name, $row->$name);
        }
    }

    /**
     * Checks for types of attributes
     * 
     * $attributes array must have this format:
     * [ATTRIBUTE_NAME => EXPECTED_TYPE]
     * 
     * If EXPECTED_TYPE is nullable than it must start with question mark.
     * E.g.:
     * 
     * ['name' => 'string', 'description' => '?string']
     * 
     * @param DatabaseRow $row DatabaseRow instance containing data
     * @param array $attributes Attributes array
     * @return void
     * @throws KeyInArrayDoesNotExistException
     * @throws TypeException
     */
    protected static function checkTypes(DatabaseRow $row, array $attributes) {
        foreach($attributes as $name => $type) {
            $nullable = false;
            if($type[0] == '?') {
                $nullable = true;
                $type = substr($type, 1);
            }

            self::checkType($row, $name, $type, $nullable);
        }
    }
}

?>