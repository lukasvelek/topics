<?php

namespace App\Exceptions;

use Throwable;

class CacheException extends AException {
    public function __construct(string $message, string $namespace, ?Throwable $previous = null) {
        parent::__construct('CacheException', $message . ' [' . $namespace . ']', $previous);
    }
}

?>