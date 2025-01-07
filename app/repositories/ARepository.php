<?php

namespace App\Repositories;

use App\Core\Caching\CacheFactory;
use App\Core\DatabaseConnection;
use App\Exceptions\DatabaseExecutionException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Managers\EntityManager;
use QueryBuilder\ExpressionBuilder;
use QueryBuilder\QueryBuilder;

/**
 * Common class for all repositories
 * 
 * @author Lukas Velek
 */
abstract class ARepository {
    private DatabaseConnection $conn;
    protected Logger $logger;
    private TransactionLogRepository $tlr;
    protected CacheFactory $cacheFactory;

    /**
     * Class constructor
     * 
     * @param DatabaseConnection $conn Database connection instance
     * @param Logger $logger Logger instance
     */
    protected function __construct(DatabaseConnection $conn, Logger $logger) {
        $this->conn = $conn;
        $this->logger = $logger;
        $this->cacheFactory = new CacheFactory($logger->getCfg());

        $this->tlr = new TransactionLogRepository($this->conn, $this->logger);
    }

    /**
     * Returns a new instance of QueryBuilder
     * 
     * @param string $method Method name
     * @return QueryBuilder New QueryBuilder instance
     */
    protected function qb(string $method = __METHOD__) {
        return new QueryBuilder($this->conn, $this->logger, $method);
    }

    /**
     * Returns a new instance of ExpressionBuilder
     * 
     * @return ExpressionBuilder New ExpressionBuilder instance
     */
    protected function xb() {
        return new ExpressionBuilder();
    }

    /**
     * Begins a database transaction
     * 
     * @param ?string $method Method name
     * @return bool True on success or false on failure
     */
    public function beginTransaction(?string $method = null) {
        $result = $this->conn->beginTransaction();
        if($result) {
            $this->logger->warning('Transaction begun.', $method ?? __METHOD__);
        }
        return $result;
    }

    /**
     * Rolls back current database transaction
     * 
     * @param ?string $method Method name
     * @return bool True on success or false on failure
     */
    public function rollback(?string $method = null) {
        $result = $this->conn->rollback();
        if($result) {
            $this->logger->warning('Transaction rolled back.', $method ?? __METHOD__);
        }
        return $result;
    }

    /**
     * Commits current database transaction
     * 
     * @param ?string $userId Calling user ID
     * @param string $method Method name
     * @return bool True on success or false on failure
     */
    public function commit(?string $userId, string $method) {
        $result = $this->conn->commit();
        if($result) {
            $sql = '';
            if(!$this->logTransaction($userId, $method, $sql)) {
                $this->rollback();
                throw new DatabaseExecutionException('Could not log transcation. Rolling back.', $sql);
            }
            $this->logger->warning('Transaction commited.', __METHOD__);
        }
        return $result;
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

    public function tryCommit(string $userId, string $method) {
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

    private function logTransaction(?string $userId, string $method, string &$sql) {
        $transactionId = $this->createEntityId(EntityManager::TRANSACTIONS);

        return $this->tlr->createNewEntry($transactionId, $userId, $method, $sql);
    }

    public function createEntityId(string $category) {
        $em = new EntityManager($this->logger, new ContentRepository($this->conn, $this->logger));

        return $em->generateEntityId($category);
    }

    protected function deleteEntryById(string $tableName, string $keyName, string $keyValue) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from($tableName)
            ->where($keyName . ' = ?', [$keyValue])
            ->execute();

        return $qb->fetchBool();
    }
}

?>