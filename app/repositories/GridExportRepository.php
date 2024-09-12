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

    public function getWaitingUnlimitedExports(int $maxCount) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['hash'])
            ->from('grid_exports')
            ->where('entryCount >= ?', [$maxCount])
            ->andWhere('filename IS NULL')
            ->andWhere('dateFinished IS NULL')
            ->execute();

        $hashes = [];
        while($row = $qb->fetchAssoc()) {
            $hashes[] = $row['hash'];
        }

        return $hashes;
    }

    public function getExportByHash(string $hash) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('grid_exports')
            ->where('hash = ?', [$hash])
            ->execute();

        return GridExportEntity::createEntityFromDbRow($qb->fetch());
    }
}

?>