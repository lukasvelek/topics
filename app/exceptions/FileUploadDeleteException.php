<?php

namespace App\Exceptions;

use Throwable;

class FileUploadDeleteException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('FileUploadDeleteException', $message, $previous);
    }
}

?>