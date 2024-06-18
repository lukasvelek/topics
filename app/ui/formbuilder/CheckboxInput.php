<?php

namespace App\UI\FormBuilder;

class CheckboxInput extends AInput {
    public function __construct(string $name, bool $checked = false) {
        parent::__construct('checkbox');

        $this->name = $name;

        if($checked) {
            $this->checked = null;
        }
    }
}

?>