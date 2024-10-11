<?php

namespace App\Repositories;

use App\Constants\UserProsecutionType;
use App\Core\DatabaseConnection;
use App\Entities\UserProsecutionEntity;
use App\Entities\UserProsecutionHistoryEntryEntity;
use App\Logger\Logger;
use App\Managers\EntityManager;

class UserProsecutionRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewProsecution(string $userId, int $type, string $reason, ?string $startDate = null, ?string $endDate = null) {
        $prosecutionId = $this->createEntityId(EntityManager::USER_PROSECUTIONS);

        $keys = ['userId', 'type', 'reason', 'prosecutionId'];
        $values = [$userId, $type, $reason, $prosecutionId];

        if($type != UserProsecutionType::PERMA_BAN) {
            $keys[] = 'startDate';
            $keys[] = 'endDate';

            $values[] = $startDate;
            $values[] = $endDate;
        }

        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_prosecutions', $keys)
            ->values($values)
            ->execute();

        return $qb->fetch();
    }

    public function getLastProsecutionForUserId(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_prosecutions')
            ->where('userId = ?', [$userId])
            ->orderBy('endDate', 'DESC')
            ->limit(1)
            ->execute();

        $prosecution = null;
        while($row = $qb->fetchAssoc()) {
            $prosecution = UserProsecutionEntity::createEntityFromDbRow($row);
        }

        return $prosecution;
    }

    public function getActiveProsecutionsCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(prosecutionId) AS cnt'])
            ->from('user_prosecutions')
            ->where(
                $this->xb()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::PERMA_BAN])
                    ->rb()
                    ->or()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::BAN])
                        ->andWhere('endDate > CURRENT_TIMESTAMP()')
                    ->rb()
                    ->or()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::WARNING])
                        ->andWhere('endDate > CURRENT_TIMESTAMP()')
                    ->rb()
                ->build()
            );

        return $qb->execute()->fetch('cnt');
    }

    public function getActiveProsecutionsForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_prosecutions')
            ->where(
                $this->xb()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::PERMA_BAN])
                    ->rb()
                    ->or()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::BAN])
                        ->andWhere('endDate > CURRENT_TIMESTAMP()')
                    ->rb()
                    ->or()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::WARNING])
                        ->andWhere('endDate > CURRENT_TIMESTAMP()')
                    ->rb()
                ->build()
            );

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $prosecutions = [];
        while($row = $qb->fetchAssoc()) {
            $prosecutions[] = UserProsecutionEntity::createEntityFromDbRow($row);
        }

        return $prosecutions;
    }

    public function composeQueryForProsecutions() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_prosecutions')
            ->where(
                $this->xb()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::PERMA_BAN])
                    ->rb()
                    ->or()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::BAN])
                        ->andWhere('endDate > CURRENT_TIMESTAMP()')
                    ->rb()
                    ->or()
                    ->lb()
                        ->where('type = ?', [UserProsecutionType::WARNING])
                        ->andWhere('endDate > CURRENT_TIMESTAMP()')
                    ->rb()
                ->build()
            );

        return $qb;
    }

    public function createNewUserProsecutionHistoryEntry(string $prosecutionId, string $userId, string $text) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_prosecutions_history', ['prosecutionId', 'userId', 'commentText'])
            ->values([$prosecutionId, $userId, $text])
            ->execute();

        return $qb->fetch();
    }

    public function updateProsecution(string $prosecutionId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_prosecutions')
            ->set($data)
            ->where('prosecutionId = ?', [$prosecutionId])
            ->execute();

        return $qb->fetch();
    }

    public function getProsecutionById(string $prosecutionId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_prosecutions')
            ->where('prosecutionId = ?', [$prosecutionId])
            ->execute();

        return UserProsecutionEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getProsecutionHistoryEntryCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(historyId) AS cnt'])
            ->from('user_prosecutions_history')
            ->execute();

        return $qb->fetch('cnt');
    }

    public function composeQueryForProsecutionLogHistory() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_prosecutions_history')
            ->orderBy('dateCreated', 'DESC');

        return $qb;
    }

    public function getProsecutionHistoryEntriesForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_prosecutions_history')
            ->orderBy('dateCreated', 'DESC');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $entries = [];
        while($row = $qb->fetchAssoc()) {
            $entries[] = UserProsecutionHistoryEntryEntity::createEntityFromDbRow($row);
        }

        return $entries;
    }
}

?>