<?php

namespace App\Exceptions;

use Throwable;

class AjaxRequestException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        if($previous !== null) {
            $message .= ' Reason: ' . $previous->getMessage();
        }

        parent::__construct('AjaxRequestException', $message, $previous);
    }
}

?>