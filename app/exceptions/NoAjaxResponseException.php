<?php

namespace App\Exceptions;

use Throwable;

class NoAjaxResponseException extends AException {
    public function __construct(?Throwable $previous = null) {
        parent::__construct('NoAjaxResponseException', 'No ajax response is defined. This is restricted.', $previous);
    }
}

?>