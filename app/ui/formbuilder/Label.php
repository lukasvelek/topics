<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class Label implements IRenderable {
    private string $text;
    private ?string $for;

    public function __construct(string $text, ?string $for = null) {
        $this->text = $text;
        $this->for = $for;
    }

    public function render() {
        $code = '<label';

        if($this->for !== null) {
            $code .= ' for="' . $this->for . '"';
        }

        $code .= '>' . $this->text . '</label>';

        return $code;
    }
}

?>