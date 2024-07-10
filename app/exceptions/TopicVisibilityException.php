<?php

namespace App\Exceptions;

use Throwable;

class TopicVisibilityException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('TopicVisibilityException', $message, $previous, false);
    }
}

?>