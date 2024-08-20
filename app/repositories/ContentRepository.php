<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class ContentRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function checkIdIsUnique(string $tableName, string $primaryKeyName, string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from($tableName)
            ->where($primaryKeyName . ' = ?', [$id])
            ->execute();

        if($qb->fetch($primaryKeyName)) {
            return false;
        } else {
            return true;
        }
    }
}

?>