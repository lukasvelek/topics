<?php

namespace App\Exceptions;

use Throwable;

class TypeException extends AException {
    public function __construct(string $expectedType, string $name, mixed $value, ?Throwable $previous = null) {
        $message = sprintf('Variable \'%s\' has unexpected type. Expected type was \'%s\'. Variable\'s value is \'%s\'.', $name, $expectedType, $value);
        parent::__construct('TypeException', $message, $previous);
    }
}

?>