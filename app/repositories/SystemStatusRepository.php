<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\SystemStatusEntity;
use App\Logger\Logger;

class SystemStatusRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllStatuses() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('system_status')
            ->execute();

        $statuses = [];
        while($row = $qb->fetchAssoc()) {
            $statuses[] = SystemStatusEntity::createEntityFromDbRow($row);
        }

        return $statuses;
    }
}

?>