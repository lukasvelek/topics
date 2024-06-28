<?php

namespace App\UI\FormBuilder;

class FormResponse {
    private array $__keys;

    public function __construct() {
        $this->__keys = [];
    }

    public function __set(string $key, mixed $value) {
        $this->$key = $value;
        $this->__keys[] = $key;
    }

    public function __get(string $key) {
        return $this->$key;
    }

    public static function createFormResponseFromPostData(array $postData) {
        $fr = new self();

        foreach($postData as $k => $v) {
            $fr->$k = $v;
        }

        return $fr;
    }

    public function evalBool(mixed $value1, mixed $value2) {
        return $value1 == $value2;
    }
}

?>