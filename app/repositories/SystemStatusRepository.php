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

    public function getSystemStatusById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('system_status')
            ->where('systemId = ?', [$id])
            ->execute();

        if($qb->fetch() !== null) {
            return SystemStatusEntity::createEntityFromDbRow($qb->fetch());
        } else {
            return null;
        }
    }

    public function updateStatus(int $id, int $status, bool $clearDescription = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('system_status')
            ->set(['status' => $status])
            ->where('systemId = ?', [$id])
            ->execute();

        if($clearDescription) {
            $qb->clean();

            $qb ->update('system_status')
                ->set(['description' => NULL])
                ->where('systemId = ?', [$id])
                ->execute();
        }

        return true;
    }
}

?>