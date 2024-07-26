<?php

namespace App\Exceptions;

use Throwable;

class StaticPageException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('StaticPageException', $message, $previous);
    }
}

?>