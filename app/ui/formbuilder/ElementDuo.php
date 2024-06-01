<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class ElementDuo implements IRenderable {
    private IRenderable $element;
    private IRenderable $label;

    public function __construct(IRenderable $element, IRenderable $label) {
        $this->element = $element;
        $this->label = $label;
    }

    public function render() {
        $code = '<span>';

        $code .= $this->label->render() . '<br>';
        $code .= $this->element->render();

        $code .= '</span>';

        return $code;
    }
}

?>