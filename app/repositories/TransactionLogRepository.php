<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\TransactionEntity;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

class TransactionLogRepository {
    private DatabaseConnection $db;
    private Logger $logger;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    private function qb(string $method) {
        return new QueryBuilder($this->db, $this->logger, $method);
    }

    public function getTransactionsForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('transaction_log')
            ->orderBy('dateCreated', 'DESC');

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = TransactionEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function createNewEntry(string $id, ?string $userId, string $methodName, string &$sql) {
        $qb = $this->qb(__METHOD__);

        $methodName = str_replace('\\', '\\\\', $methodName);

        $keys = ['transactionId', 'methodName'];
        $values = [$id, $methodName];

        if($userId !== null) {
            $keys[] = 'userId';
            $values[] = $userId;
        }

        $qb ->insert('transaction_log', $keys)
            ->values($values)
            ->execute();

        $sql = $qb->getSQL();
        
        return $qb->fetchBool();
    }
    
    public function composeQueryForTransactions() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('transaction_log')
            ->orderBy('dateCreated', 'DESC');

        return $qb;
    }
}

?>