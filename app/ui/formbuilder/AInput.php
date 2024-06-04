<?php

namespace App\UI\FormBuilder;

abstract class AInput extends AElement {
    public function __construct(string $type) {
        parent::__construct('input');

        $this->type = $type;
    }

    public function setRequired(bool $required = true) {
        if($required === true) {
            $this->required = null;
        }
    }

    public function setHidden(bool $hidden = true) {
        if($hidden === true) {
            $this->hidden = null;
        }
    }

    public function setDisabled(bool $disabled = true) {
        if($disabled === true) {
            $this->disabled = null;
        }
    }
}

?>