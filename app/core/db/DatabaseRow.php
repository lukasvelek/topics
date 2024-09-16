<?php

namespace App\Core\DB;

class DatabaseRow {
    private array $values;

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
}

?>