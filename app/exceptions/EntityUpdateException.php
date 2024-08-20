<?php

namespace App\Exceptions;

use Throwable;

class EntityUpdateException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('EntityUpdateException', $message, $previous);
    }
}

?>