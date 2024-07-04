<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Entities\GroupEntity;
use App\Entities\GroupMembershipEntity;
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

    public function getGroupCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(groupId) AS cnt'])
            ->from('groups')
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getGroupsForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('groups');

        if($limit > 0){
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $groups = [];
        while($row = $qb->fetchAssoc()) {
            $groups[] = GroupEntity::createEntityFromDbRow($row);
        }

        return $groups;
    }

    public function getGroupMembersCount(int $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(membershipId) AS cnt'])
            ->from('group_membership')
            ->where('groupId = ?', [$groupId])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getGroupMembersForGrid(int $groupId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('group_membership')
            ->where('groupId = ?', [$groupId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $members = [];
        while($row = $qb->fetchAssoc()) {
            $members[] = GroupMembershipEntity::createEntityFromDbRow($row);
        }

        return $members;
    }

    public function getGroupById(int $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('groups')
            ->where('groupId = ?', [$groupId]);

        $entity = $this->cache->loadCache($groupId, function() use ($qb) {
            $row = $qb->execute()->fetch();

            return GroupEntity::createEntityFromDbRow($row);
        }, 'groups');

        return $entity;
    }

    public function getGroupMemberUserIds(int $groupId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['userId'])
            ->from('group_membership')
            ->where('groupId = ?', [$groupId])
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['userId'];
        }

        return $ids;
    }

    public function addGroupMember(int $groupId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('group_membership', ['groupId', 'userId'])
            ->values([$groupId, $userId])
            ->execute();

        return $qb->fetch();
    }

    public function removeGroupMember(int $groupId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('group_membership')
            ->where('userId = ?', [$userId])
            ->andWhere('groupId = ?', [$groupId])
            ->execute();

        return $qb->fetch();
    }
}

?>