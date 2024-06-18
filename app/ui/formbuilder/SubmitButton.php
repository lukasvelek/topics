<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

class SubmitButton extends AInput {
    public function __construct(string $text = 'Submit') {
        parent::__construct('submit');

        $this->value = $text;
        $this->id = 'formSubmit';
    }
}

?>