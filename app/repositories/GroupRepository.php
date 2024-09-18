<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\DatabaseConnection;
use App\Entities\GroupEntity;
use App\Entities\GroupMembershipEntity;
use App\Logger\Logger;

class GroupRepository extends ARepository {
    private Cache $cache;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);

        $this->cache = $this->cacheFactory->getCache(CacheNames::GROUPS);
    }

    public function isUserMemberOfGroup(string $userId, int $groupId) {
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

        $this->applyGridValuesToQb($qb, $limit, $offset);

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

        $this->applyGridValuesToQb($qb, $limit, $offset);

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

        return $this->cache->load($groupId, function() use ($qb) {
            return GroupEntity::createEntityFromDbRow($qb->execute()->fetch());
        });
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

    public function addGroupMember(int $groupId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('group_membership', ['groupId', 'userId'])
            ->values([$groupId, $userId])
            ->execute();

        return $qb->fetch();
    }

    public function removeGroupMember(int $groupId, string $userId) {
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