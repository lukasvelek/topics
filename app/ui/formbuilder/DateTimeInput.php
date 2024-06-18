<?php

namespace App\UI\FormBuilder;

class DateTimeInput extends AInput {
    public function __construct(string $name, ?string $value) {
        parent::__construct('datetime-local');

        $this->name = $name;
        $this->id = $name;

        if($value !== null) {
            $this->value = $value;
        }
    }
}

?>