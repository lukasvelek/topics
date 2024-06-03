<?php

namespace App\Exceptions;

use Throwable;

class ApplicationInitializationException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('ApplicationInitializationException', $message, $previous);
    }
}

?>