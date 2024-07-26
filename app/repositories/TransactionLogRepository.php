<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\TransactionEntity;
use App\Logger\Logger;

class TransactionLogRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
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
}

?>