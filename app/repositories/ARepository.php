<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

abstract class ARepository {
    private DatabaseConnection $conn;
    protected Logger $logger;

    protected function __construct(DatabaseConnection $conn, Logger $logger) {
        $this->conn = $conn;
        $this->logger = $logger;
    }

    protected function qb(string $method = __METHOD__) {
        return new QueryBuilder($this->conn, $this->logger, $method);
    }
}

?>