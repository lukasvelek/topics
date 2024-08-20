<?php

namespace App\Exceptions;

use Throwable;

class UserFollowException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('UserFollowException', $message, $previous);
    }
}

?>