<?php

namespace App\Core\DB;

/**
 * Class representing a single row from database query
 * 
 * @author Lukas Velek
 */
class DatabaseRow {
    private array $values;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->values = [];
    }
    
    /**
     * Sets the value
     * 
     * @param mixed $key Key
     * @param mixed $value Value
     */
    public function __set(mixed $key, mixed $value) {
        $this->values[$key] = $value;
    }

    /**
     * Returns the value
     * 
     * @param mixed $key Key
     * @return mixed Value
     */
    public function __get(mixed $key) {
        if(array_key_exists($key, $this->values)) {
            return $this->values[$key];
        } else {
            return null;
        }
    }

    /**
     * Returns all keys
     * 
     * @return array All keys
     */
    public function getKeys() {
        return array_keys($this->values);
    }

    /**
     * Creates a DatabaseRow instance from mysqli_result $row
     * 
     * @param mixed $row mysqli_result row
     * @return self
     */
    public static function createFromDbRow($row) {
        $obj = new self();

        foreach($row as $k => $v) {
            $obj->$k = $v;
        }

        return $obj;
    }
}

?>