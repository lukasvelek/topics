<?php

namespace App\Exceptions;

use Throwable;

class URLParamIsNotDefinedException extends AException {
    public function __construct(string $paramName, ?Throwable $previous = null) {
        parent::__construct(sprintf('URL parameter \'%s\' is not defined!', $paramName), $previous);
    }
}

?>