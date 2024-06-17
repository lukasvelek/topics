<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class ElementDuo implements IRenderable {
    private IRenderable $element;
    private IRenderable $label;
    private string $name;

    public function __construct(IRenderable $element, IRenderable $label, string $name) {
        $this->element = $element;
        $this->label = $label;
        $this->name = $name;
    }

    public function render() {
        $code = '<span id="span_' . $this->name . '">';

        $code .= $this->label->render() . '<br>';
        $code .= $this->element->render();

        $code .= '</span>';

        return $code;
    }
}

?>