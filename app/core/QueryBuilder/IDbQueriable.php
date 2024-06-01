<?php

namespace QueryBuilder;

/**
 * Interface that allows processing SQL query
 * 
 * @author Lukas Velek
 */
interface IDbQueriable {
    function query(string $sql, array $params = []);
}

?>