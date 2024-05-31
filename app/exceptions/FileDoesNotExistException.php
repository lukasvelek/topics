<?php

namespace App\Exceptions;

use Throwable;

class FileDoesNotExistException extends AException {
    public function __construct(string $filename, ?Throwable $previous = null) {
        parent::__construct($this->toString($filename), $previous);
    }

    private function toString(string $filename) {
        return sprintf('File \'%s\' does not exist!', $filename);
    }
}

?>