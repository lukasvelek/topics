<?php

namespace App\Exceptions;

use Throwable;

class CouldNotFetchLastEntityIdException extends AException {
    public function __construct(string $entityType, ?Throwable $previous = null) {
        parent::__construct('CouldNotFetchLastEntityIdException', sprintf('Last entry for entity type %s could not be fetched.', $entityType), $previous);
    }
}

?>