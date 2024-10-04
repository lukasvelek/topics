<?php

namespace App\Exceptions;

use Throwable;

class FileLockHandleObtainException extends AFileLockException {
    public function __construct(string $filename, ?Throwable $previous = null) {
        parent::__construct('FileLockHandleObtainException', 'Cannot obtain file lock handle for file \'' . $filename . '\'.', $previous);
    }
}

?>