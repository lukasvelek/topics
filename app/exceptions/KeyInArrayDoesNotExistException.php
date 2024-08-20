<?php

namespace App\Exceptions;

use Throwable;

class KeyInArrayDoesNotExistException extends AException {
    public function __construct(string $keyName, string $arrayName, ?Throwable $previous = null) {
        $message = sprintf('Key \'%s\' does not exist in array \'%s\'.', $keyName, $arrayName);
        parent::__construct('KeyInArrayDoesNotExistException', $message, $previous);
    }
}

?>