<?php

namespace App\Exceptions;

use Throwable;

class UnauthorizedAccessException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('UnauthorizedAccessException', $message, $previous);
    }
}

?>