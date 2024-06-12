<?php

namespace App\UI\FormBuilder;

class Button extends AElement {
    public function __construct(string $text, string $onclickAction) {
        parent::__construct('button', $text);

        $this->type = 'button';
        $this->onclick = $onclickAction;
    }
}

?>