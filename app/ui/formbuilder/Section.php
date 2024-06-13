<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class Section implements IRenderable {
    private array $elements;
    private bool $hidden;
    public string $name;
    public ?Section $parentSection;

    public function __construct(string $name, ?Section $parentSection = null) {
        $this->name = $name;
        $this->elements = [];
        $this->parentSection = $parentSection;
        $this->hidden = false;
    }

    public function setHidden(bool $hidden = true) {
        $this->hidden = $hidden;
    }

    public function addElement(string $name, IRenderable $object) {
        $this->elements[$name] = $object;
    }

    public function render() {
        $code = '<div id="' . $this->name . '"';

        if($this->hidden) {
            $code .= ' style="visibility: hidden; height: 0px"';
        }
        
        $code .= '>';

        foreach($this->elements as $element) {
            $code .= $element->render();
        }

        $code .= '</div>';

        return $code;
    }
}

?>