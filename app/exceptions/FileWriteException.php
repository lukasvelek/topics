<?php

namespace App\Exceptions;

use Throwable;

class FileWriteException extends AException {
    public function __construct(string $filename, ?Throwable $previous = null) {
        parent::__construct('FileWriteException', 'Cannot write to file \'' . $filename . '\'.', $previous);
    }
}

?>