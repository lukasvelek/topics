<?php

namespace App\UI\FormBuilder;

class SubmitButton extends AInput {
    public function __construct(string $text = 'Submit', bool $disabled = false, string $name = '') {
        parent::__construct('submit');
        $this->name = $name;

        $this->value = $text;
        $this->id = 'formSubmit';

        $this->setDisabled($disabled);
    }

    public function setCenter(bool $center = true) {
        $this->centered = $center;

        return $this;
    }
}

?>