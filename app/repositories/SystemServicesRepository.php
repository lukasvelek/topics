<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\SystemServiceEntity;
use App\Logger\Logger;

class SystemServicesRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getAllServices() {
        $qb = $this->qb(__METHOD__);
        
        $qb ->select(['*'])
            ->from('system_services')
            ->orderBy('dateStarted', 'DESC')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = SystemServiceEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getServiceById(int $id) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->select(['*'])
            ->from('system_services')
            ->where('serviceId = ?', [$id])
            ->execute();

        return SystemServiceEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getServiceByTitle(string $title) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->select(['*'])
            ->from('system_services')
            ->where('title = ?', [$title])
            ->execute();

        return SystemServiceEntity::createEntityFromDbRow($qb->fetch());
    }

    public function updateService(int $serviceId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('system_services')
            ->set($data)
            ->where('serviceId = ?', [$serviceId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>