<?php

namespace App\Exceptions;

use Throwable;

class ActionDoesNotExistException extends AException {
    public function __construct(string $action, ?Throwable $previous = null) {
        parent::__construct(sprintf('Action \'%s\' does not exist!', $action), $previous);
    }
}

?>