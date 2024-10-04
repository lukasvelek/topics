<?php

namespace App\Exceptions;

use Throwable;

class FileLockException extends AFileLockException {
    public function __construct(string $filename, ?Throwable $previous = null) {
        parent::__construct('FileLockException', 'Cannot lock file \'' . $filename . '\'.', $previous);
    }
}

?>