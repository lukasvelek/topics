<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class GroupRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function isUserMemberOfGroup(int $userId, int $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_membership')
            ->where('userId = ?', [$userId])
            ->andWhere('groupId = ?', [$groupId])
            ->execute();

        return $qb->fetch() !== null;
    }
}

?>