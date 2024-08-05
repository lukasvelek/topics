<?php

namespace App\Exceptions;

use Throwable;

class MailSendException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('MailSendException', $message, $previous);
    }
}

?>