<?php

namespace App\UI\FormBuilder;

class TextInput extends AInput {
    public function __construct(string $name, mixed $value) {
        parent::__construct('text');

        $this->name = $name;

        if($value !== null) {
            $this->value = $value;
        }
    }
}

?>