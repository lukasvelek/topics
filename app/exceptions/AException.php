<?php

namespace App\Exceptions;

use Exception;
use Throwable;

abstract class AException extends Exception {
    protected function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct($message, 9999, $previous);
    }
}

?>