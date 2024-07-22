<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class CallbackExecutionException extends AException {
    public function __construct(Exception $callbackException, ?Throwable $previous = null) {
        parent::__construct('CallbackExecutionException', 'Could not execute callback. Reason: ' . $callbackException->getMessage(), $previous);
    }
}

?>