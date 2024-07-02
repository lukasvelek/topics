<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class RadioInputGroup implements IRenderable {
    private array $radios;

    public function __construct() {
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
}

?>