<?php

namespace App\Exceptions;

use Throwable;

class FileUploadException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('FileUploadException', $message, $previous);
    }
}

?>