<?php

namespace QueryBuilder;

use Exception;

/**
 * ILoggerCallable is an interface that must be implemented by a class that allows logging.
 * 
 * @author Lukas Velek
 */
interface ILoggerCallable {
    function sql(string $sql, string $method, ?int $msTaken);
    function exception(Exception $e, string $method);
}

?>