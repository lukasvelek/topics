<?php

namespace App\UI\FormBuilder;

class RadioInputGroup implements IFormRenderable {
    private array $radios;
    private string $name;

    public function __construct(string $name) {
        $this->name = $name;
        $this->radios = [];
    }

    public function addRadio(RadioInput $radio) {
        $this->radios[] = $radio;
    }

    public function render() {
        $code = '<span>';

        foreach($this->radios as $radio) {
            $code .= $radio->render();
        }

        $code .= '</span>';

        return $code;
    }

    public function getName() {
        return $this->name;
    }
}

?>