<?php

namespace App\UI\FormBuilder;

class RadioInput extends AInput {
    private Label $label;

    public function __construct(string $name, mixed $value, string $text) {
        parent::__construct('radio');

        $this->name = $name;
        $this->value = $value;

        $this->label = new Label($text, $name);
    }

    public function setChecked(bool $checked = true) {
        if($checked === true) {
            $this->checked = null;
        }
    }

    public function render() {
        $code = parent::render();

        $code .= $this->label->render();

        return $code;
    }
}

?>