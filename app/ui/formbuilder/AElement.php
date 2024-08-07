<?php

namespace App\UI\FormBuilder;

abstract class AElement implements IFormRenderable {
    private array $__elements;
    private string $__name;
    
    protected function __construct(string $name, mixed $content = null) {
        $this->$name = null;
        $this->__name = $name;
        $this->content = $content;
    }

    public function __set(string $name, mixed $value) {
        $this->$name = $value;
        $this->__elements[] = $name;
    }

    public function __get(string $name) {
        return $this->$name;
    }

    public function render() {
        $code = '<' . $this->__name . ' ';

        $elements = [];

        foreach($this->__elements as $element) {
            if(in_array($element, ['content', 'input', 'select', 'option', 'label', 'textarea', 'button'])) continue;

            $value = $this->$element;

            if($value === null) {
                $elements[] = $element;
            } else {
                $elements[] = $element . '="' . $value . '"';
            }
        }

        $code .= implode(' ', $elements) . '>';

        
        if(in_array($this->__name, ['select', 'label', 'option', 'textarea', 'button'])) {
            $code .= $this->content . '</' . $this->__name . '>';
        }

        return $code;
    }

    public function getName() {
        return $this->name;
    }
}

?>