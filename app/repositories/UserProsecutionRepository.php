<?php

namespace App\Repositories;

use App\Constants\UserProsecutionType;
use App\Core\DatabaseConnection;
use App\Entities\UserProsecutionEntity;
use App\Logger\Logger;

class UserProsecutionRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewProsecution(int $userId, int $type, string $reason, ?string $startDate = null, ?string $endDate = null) {
        $keys = ['userId', 'type', 'reason'];
        $values = [$userId, $type, $reason];

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

    public function getLastProsecutionForUserId(int $userId) {
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

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $prosecutions = [];
        while($row = $qb->fetchAssoc()) {
            $prosecutions[] = UserProsecutionEntity::createEntityFromDbRow($row);
        }

        return $prosecutions;
    }

    public function createNewUserProsecutionHistoryEntry(int $prosecutionId, int $userId, string $text) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_prosecutions_history', ['prosecutionId', 'userId', 'commentText'])
            ->values([$prosecutionId, $userId, $text])
            ->execute();

        return $qb->fetch();
    }

    public function updateProsecution(int $prosecutionId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_prosecutions')
            ->set($data)
            ->where('prosecutionId = ?', [$prosecutionId])
            ->execute();

        return $qb->fetch();
    }

    public function getProsecutionById(int $prosecutionId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_prosecutions')
            ->where('prosecutionId = ?', [$prosecutionId])
            ->execute();

        return UserProsecutionEntity::createEntityFromDbRow($qb->fetch());
    }
}

?>