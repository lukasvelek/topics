<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class CallbackExecutionException extends AException {
    public function __construct(Exception $callbackException, array $args = [], ?Throwable $previous = null) {
        parent::__construct('CallbackExecutionException', 'Could not execute callback with parameters: [' . implode(', ', $args) . ']. Reason: ' . $callbackException->getMessage(), $previous);
    }
}

?>