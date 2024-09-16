<?php

namespace App\UI\FormBuilder;

class TextArea extends AElement {
    public function __construct(string $name, ?string $content = null) {
        parent::__construct('textarea', $content);

        $this->name = $name;
    }

    public function setId(string $id) {
        $this->id = $id;
    }

    public function setRows(int $rows = 3) {
        $this->rows = $rows;
    }

    public function setRequired(bool $required = true) {
        if($required === true) {
            $this->required = null;
        }
    }

    public function setPlaceholder(string $text) {
        $this->placeholder = $text;
    }
}

?>