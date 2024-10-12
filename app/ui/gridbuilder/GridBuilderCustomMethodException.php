<?php

namespace App\UI\GridBuilder;

use App\Exceptions\AException;
use Throwable;

/**
 * @deprecated
 */
class GridBuilderCustomMethodException extends AException {
    public function __construct(string $message, ?Throwable $previous = null) {
        parent::__construct('GridBuilderCustomMethodException', $message, $previous);
    }
}

?>