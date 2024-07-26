<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Core\HashManager;
use App\Exceptions\DatabaseExecutionException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use QueryBuilder\ExpressionBuilder;
use QueryBuilder\QueryBuilder;

abstract class ARepository {
    private DatabaseConnection $conn;
    protected Logger $logger;
    protected CacheManager $cache;

    protected function __construct(DatabaseConnection $conn, Logger $logger) {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->cache = new CacheManager($logger);
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

    public function commit(int $userId, string $method) {
        $sql = '';
        if(!$this->logTransaction($userId, $method, $sql)) {
            $this->rollback();
            throw new DatabaseExecutionException('Could not log transcation. Rolling back.', $sql);
        }
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

    public function tryCommit(int $userId, string $method) {
        $result = $this->commit($userId, $method);

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

    public function getLogger() {
        return $this->logger;
    }

    protected function applyGridValuesToQb(QueryBuilder &$qb, int $limit, int $offset) {
        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }
    }

    private function logTransaction(int $userId, string $method, string &$sql) {
        $qb = $this->qb(__METHOD__);

        $transactionId = HashManager::createHash(64, false);

        $qb ->insert('transaction_log', ['transactionId', 'userId', 'methodName'])
            ->values([$transactionId, $userId, $method])
            ->execute();

        $sql = $qb->getSQL();
        
        return $qb->fetchBool();
    }
}

?>