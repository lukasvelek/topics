<?php

namespace App\Exceptions;

use Throwable;

class ModuleDoesNotExistException extends AException {
    public function __construct(string $module, ?Throwable $previous = null) {
        parent::__construct(sprintf('Module \'%s\' does not exist!', $module), $previous);
    }
}

?>