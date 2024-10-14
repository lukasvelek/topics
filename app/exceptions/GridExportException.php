<?php

namespace App\Exceptions;

use Throwable;

class GridExportException extends AException {
    public function __construct(?string $message, ?Throwable $previous = null) {
        parent::__construct('GridExportException', $message ?? 'Could not export grid.', $previous);
    }
}

?>