<?php

namespace App\Exceptions;

use Throwable;

class ModuleDoesNotExistException extends AException {
    public function __construct(string $module, ?Throwable $previous = null) {
        parent::__construct('ModuleDoesNotExistException', sprintf('Module \'%s\' does not exist!', $module), $previous);
    }
}

?>