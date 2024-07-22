<?php

namespace App\UI\FormBuilder;

class FileInput extends AInput {
    public function __construct(string $name) {
        parent::__construct('file');

        $this->name = $name;
        $this->id = $name;
    }
}

?>