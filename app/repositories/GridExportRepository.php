<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\GridExportEntity;
use App\Logger\Logger;
use App\Managers\EntityManager;

class GridExportRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getUserExportsForGrid(string $userId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('grid_exports')
            ->where('userId = ?', [$userId])
            ->orderBy('dateCreated', 'DESC');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = GridExportEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getExportsForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('grid_exports')
            ->orderBy('dateCreated', 'DESC');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = GridExportEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function createNewExport(string $userId, string $hash, string $gridName) {
        $exportId = $this->createEntityId(EntityManager::GRID_EXPORTS);

        $qb = $this->qb(__METHOD__);

        $qb ->insert('grid_exports', ['exportId', 'userId', 'hash', 'gridName'])
            ->values([$exportId, $userId, $hash, $gridName])
            ->execute();
        
        return $qb->fetchBool();
    }

    public function updateExportByHash(string $hash, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('grid_exports')
            ->set($data)
            ->where('hash = ?', [$hash])
            ->execute();

        return $qb->fetchBool();
    }
}

?>