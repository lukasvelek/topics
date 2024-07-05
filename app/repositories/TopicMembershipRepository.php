<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\TopicMemberEntity;
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

        return ($qb->fetch('membershipId') !== null);
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

    public function getTopicMembersForGrid(int $topicId, int $limit, int $offset, bool $orderByRoleDesc) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }
        if($orderByRoleDesc) {
            $qb->orderBy('role', 'DESC');
        }

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = TopicMemberEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getMembershipForUserInTopic(int $userId, int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return TopicMemberEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getUserMembershipsInTopics(int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['topicId'])
            ->from('topic_membership')
            ->where('userId = ?', [$userId])
            ->execute();

        $topicIds = [];
        while($row = $qb->fetchAssoc()) {
            $topicIds[] = $row['topicId'];
        }

        return $topicIds;
    }

    public function getTopicMemberCount(int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(userId) AS cnt'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetch('cnt');
    }
}

?>