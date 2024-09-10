<?php

namespace QueryBuilder;

use App\Exceptions\AException;
use Throwable;

class QueryBuilderException extends AException {
    public function __construct(string $message, ?string $sql = null, ?Throwable $previous = null) {
        if($sql !== null) {
            $message .= ' SQL: ' . $sql;
        }
        parent::__construct('QueryBuilderException', $message, $previous);
    }
}

?>