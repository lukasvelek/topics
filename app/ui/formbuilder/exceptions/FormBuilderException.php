<?php

namespace App\UI\FormBuilder;

use App\Exceptions\AException;
use Throwable;

class FormBuilderException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('FormBuilderException', $message, $previous);
    }
}

?>