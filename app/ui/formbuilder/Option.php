<?php

namespace App\UI\FormBuilder;

class Option extends AElement {
    public function __construct(mixed $value, string $text, bool $selected = false) {
        parent::__construct('option', $text);

        $this->value = $value;

        if($selected === true) {
            $this->selected = null;
        }
    }
}

?>