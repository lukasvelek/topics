<?php

namespace App\UI\FormBuilder;

class EmailInput extends AInput {
    public function __construct(string $name, mixed $value) {
        parent::__construct('email');

        $this->name = $name;
        $this->id = $name;

        if($value !== null) {
            $this->value = $value;
        }
    }
}

?>