<?php

namespace App\Authorizators;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use QueryBuilder\ExpressionBuilder;
use QueryBuilder\QueryBuilder;

abstract class AAuthorizator {
    private DatabaseConnection $db;
    protected Logger $logger;

    protected function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    protected function qb(string $method = __METHOD__) {
        return new QueryBuilder($this->db, $this->logger, $method);
    }
    
    protected function xb() {
        return new ExpressionBuilder();
    }
}

?>