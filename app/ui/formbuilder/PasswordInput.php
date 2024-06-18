<?php

namespace App\UI\FormBuilder;

class PasswordInput extends AInput {
    public function __construct(string $name, mixed $value) {
        parent::__construct('password');

        $this->name = $name;
        $this->id = $name;

        if($value !== null) {
            $this->value = $value;
        }
    }
}

?>