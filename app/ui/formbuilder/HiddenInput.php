<?php

namespace App\UI\FormBuilder;

class HiddenInput extends AInput {
    public function __construct(string $name, mixed $value) {
        parent::__construct('hidden');

        $this->name = $name;
        $this->id = $name;
        
        $this->value = $value;
    }
}

?>