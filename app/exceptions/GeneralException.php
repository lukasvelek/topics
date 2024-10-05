<?php

namespace App\Exceptions;

use Throwable;

class GeneralException extends AException {
    public function __construct(string $message, ?Throwable $previous = null, bool $createFile = true) {
        parent::__construct('GeneralException', $message, $previous, $createFile);
    }
}

?>