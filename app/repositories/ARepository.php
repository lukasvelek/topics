<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use QueryBuilder\ExpressionBuilder;
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

    protected function xb() {
        return new ExpressionBuilder();
    }

    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    public function rollback() {
        return $this->conn->rollback();
    }

    public function commit() {
        return $this->conn->commit();
    }

    public function tryBeginTransaction() {
        $result = $this->beginTransaction();

        if($result === false) {
            throw new GeneralException('Could not establish database transaction.');
        }

        return $result;
    }

    public function tryRollback() {
        $result = $this->rollback();

        if($result === false) {
            throw new GeneralException('Could not rollback database transaction.');
        }

        return $result;
    }

    public function tryCommit() {
        $result = $this->commit();

        if($result === false) {
            throw new GeneralException('Could not commit database transaction');
        }

        return $result;
    }
}

?>