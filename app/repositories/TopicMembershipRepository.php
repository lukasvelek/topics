<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class TopicMembershipRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function addMemberToTopic(int $topicId, int $userId, int $role) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_membership', ['topicId', 'userId', 'role'])
            ->values([$topicId, $userId, $role])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeMemberFromTopic(int $topicId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateMemberRole(int $topicId, int $userId, int $role) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topic_membership')
            ->set(['role' => $role])
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function checkIsMember(int $topicId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['membershipId'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        if($qb->fetch('membershipId') !== null) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserRoleInTopic(int $topicId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['role'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetch('role');
    }

    public function getTopicMembers(int $topicId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_membersip')
            ->where('topicId = ?', [$topicId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        
    }
}

?>