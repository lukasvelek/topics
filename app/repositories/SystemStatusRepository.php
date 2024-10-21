<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\SystemStatusEntity;
use App\Logger\Logger;

class SystemStatusRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function composeQueryForStatuses() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('system_status');

        return $qb;
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

    public function getSystemStatusById(string $id) {
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

    public function updateStatus(string $id, int $status, ?string $description) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('system_status')
            ->set(['status' => $status, 'description' => $description])
            ->where('systemId = ?', [$id])
            ->execute();

        return true;
    }

    public function getSystemStatusByName(string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('system_status')
            ->where('name = ?', [$name])
            ->execute();

        return SystemStatusEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getSystemDescription(string $name) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['description'])
            ->from('system_status')
            ->where('name = ?', [$name])
            ->execute();

        return $qb->fetch('description');
    }
}

?>