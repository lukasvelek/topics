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
    
    public function __set(mixed $key, mixed $value) {
        $this->values[$key] = $value;
    }

    public function __get(mixed $key) {
        if(array_key_exists($key, $this->values)) {
            return $this->values[$key];
        } else {
            return null;
        }
    }

    public function getKeys() {
        return array_keys($this->values);
    }
}

?>