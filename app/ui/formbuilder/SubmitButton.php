<?php

namespace App\UI\FormBuilder;

class SubmitButton extends AInput {
    public function __construct(string $text = 'Submit', bool $disabled = false) {
        parent::__construct('submit');

        $this->value = $text;
        $this->id = 'formSubmit';

        $this->setDisabled($disabled);
    }

    public function setCenter(bool $center) {
        $this->centered = $center;

        return $this;
    }
}

?>