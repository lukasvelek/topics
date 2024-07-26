<?php

namespace App\Exceptions;

use Throwable;

class UserRegistrationException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('UserRegistrationException', $message, $previous);
    }
}

?>