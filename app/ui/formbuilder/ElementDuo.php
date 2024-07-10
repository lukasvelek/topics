<?php

namespace App\UI\FormBuilder;

class ElementDuo implements IFormRenderable {
    private IFormRenderable $element;
    private IFormRenderable $label;
    private string $name;

    public function __construct(IFormRenderable $element, IFormRenderable $label, string $name) {
        $this->element = $element;
        $this->label = $label;
        $this->name = 'ed_' . $name;
    }

    public function render() {
        if(!($this->label instanceof IFormRenderable)) {
            throw new FormBuilderException('Label in ElementDuo does not implement the IFormRenderable interface.');
        }
        if(!($this->element instanceof IFormRenderable)) {
            throw new FormBuilderException('Element in ElementDuo does not implement the IFormRenderable interface.');
        }

        $code = '<span id="span_' . $this->name . '">';

        $code .= $this->label->render() . '<br>';
        $code .= $this->element->render();

        $code .= '</span>';

        return $code;
    }

    public function getElement() {
        return $this->element;
    }

    public function setElement(IFormRenderable $element) {
        $this->element = $element;
    }

    public function getLabel() {
        return $this->label;
    }

    public function setLabel(IFormRenderable $label) {
        $this->label = $label;
    }

    public function getName() {
        return $this->name;
    }
}

?>