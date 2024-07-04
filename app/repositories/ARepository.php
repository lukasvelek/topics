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
        $this->logger->warning('Transaction begun.', __METHOD__);
        return $this->conn->beginTransaction();
    }

    public function rollback() {
        $this->logger->warning('Transaction rolled back.', __METHOD__);
        return $this->conn->rollback();
    }

    public function commit() {
        $this->logger->warning('Transaction commited.', __METHOD__);
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

    public function sql(string $sql) {
        $this->logger->sql($sql, __METHOD__, null);
        return $this->conn->query($sql);
    }

    public function getQb() {
        return $this->qb(__METHOD__);
    }
}

?>