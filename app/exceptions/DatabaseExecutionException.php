<?php

namespace App\Exceptions;

use Throwable;

class DatabaseExecutionException extends AException {
    public function __construct(string $message, string $sql, ?Throwable $previous = null) {
        parent::__construct('DatabaseExecutionException', $message . '. SQL: ' . $sql, $previous);
    }
}

?>