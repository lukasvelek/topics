<?php

namespace App\Exceptions;

use Throwable;

class MethodDoesNotExistInClassException extends AException {
    public function __construct(string $methodName, string $className, ?Throwable $previous = null) {
        $message = sprintf('Method %s does not exist in class %s', $methodName, $className);
        
        parent::__construct('MethodDoesNotExistInClassException', $message, $previous);
    }
}

?>