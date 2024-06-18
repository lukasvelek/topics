<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class Label implements IRenderable {
    private string $text;
    private ?string $for;

    public function __construct(string $text, ?string $for = null, bool $required = false) {
        $this->text = $text;
        $this->for = $for;

        if($required === true) {
            $this->text .= ' <span id="label_' . $this->for . '_required" style="color: red">*</span>';
        }
    }

    public function render() {
        $code = '<label id="label_' . $this->for . '"';

        if($this->for !== null) {
            $code .= ' for="' . $this->for . '"';
        }

        $code .= '>' . $this->text . '</label>';

        return $code;
    }
}

?>