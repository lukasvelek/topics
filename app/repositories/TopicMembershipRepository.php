<?php

namespace App\Repositories;

use App\Constants\TopicMemberRole;
use App\Core\DatabaseConnection;
use App\Entities\TopicMemberEntity;
use App\Logger\Logger;

class TopicMembershipRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function addMemberToTopic(string $membershipId, string $topicId, string $userId, int $role) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_membership', ['membershipId', 'topicId', 'userId', 'role'])
            ->values([$membershipId, $topicId, $userId, $role])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeMemberFromTopic(string $topicId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateMemberRole(string $topicId, string $userId, int $role) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topic_membership')
            ->set(['role' => $role])
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function checkIsMember(string $topicId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['membershipId'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return ($qb->fetch('membershipId') !== null);
    }

    public function getUserRoleInTopic(string $topicId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['role'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetch('role');
    }

    public function composeQueryForTopicMembers(string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->orderBy('role', 'DESC');

        return $qb;
    }

    public function getTopicMembersForGrid(string $topicId, int $limit, int $offset, bool $orderByRoleDesc) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId]);

        $this->applyGridValuesToQb($qb, $limit, $offset);
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

    public function getMembershipForUserInTopic(string $userId, string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return TopicMemberEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getUserMembershipsInTopics(string $userId) {
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

    public function getTopicMemberCount(string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(userId) AS cnt'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getTopicOwner(string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['userId'])
            ->from('topic_membership')
            ->where('topicId = ?', [$topicId])
            ->andWhere('role = ?', [TopicMemberRole::OWNER])
            ->execute();

        return $qb->fetch('userId');
    }

    public function getTopicIdsForOwner(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['topicId'])
            ->from('topic_membership')
            ->where('userId = ?', [$userId])
            ->andWhere('role = ?', [TopicMemberRole::OWNER])
            ->execute();

        $topicIds = [];
        while($row = $qb->fetchAssoc()) {
            $topicIds[] = $row['topicId'];
        }

        return $topicIds;
    }
}

?>