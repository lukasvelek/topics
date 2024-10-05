<?php

namespace App\Exceptions;

use Throwable;

abstract class AFileLockException extends AException {
    public function __construct(string $name, string $message, ?Throwable $previous = null) {
        parent::__construct($name, $message, $previous);
    }
}

?>