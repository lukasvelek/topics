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
            if($type == UserProsecutionType::WARNING) {
                $startDate = date('Y-m-d H:i:s');
                $endDate = date('Y-m-d H:i:s', (time() + 86400)); // 24hrs
            }

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
}

?>