<?php

namespace App\Exceptions;

use Throwable;

class InstallationException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('InstallationException', $message, $previous);
    }
}

?>