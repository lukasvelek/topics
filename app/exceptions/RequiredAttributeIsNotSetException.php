<?php

namespace App\Exceptions;

use Throwable;

class RequiredAttributeIsNotSetException extends AException {
    public function __construct(string $attributeName, string $className, ?Throwable $previous = null) {
        $message = sprintf('Attribute %s in class %s has incorrect value.', $attributeName, $className);
        parent::__construct('RequiredAttributeIsNotSetException', $message, $previous);
    }
}

?>