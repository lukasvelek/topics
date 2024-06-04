<?php

namespace App\Exceptions;

use Throwable;

class DatabaseConnectionException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('DatabaseConnectionException', $message, $previous);
    }
}

?>