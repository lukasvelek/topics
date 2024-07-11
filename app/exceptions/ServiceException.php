<?php

namespace App\Exceptions;

use Throwable;

class ServiceException extends AException {
    public function __construct(string $text, ?Throwable $previous = null) {
        parent::__construct('ServiceException', $text, $previous);
    }
}

?>