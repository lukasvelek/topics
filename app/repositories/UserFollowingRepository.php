<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\HashManager;
use App\Entities\UserFollowEntity;
use App\Logger\Logger;
use App\Repositories\ARepository;

class UserFollowingRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function followUser(string $authorId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $followId = HashManager::createEntityId();

        $qb ->insert('user_following', ['authorId', 'userId', 'followId'])
            ->values([$authorId, $userId, $followId])
            ->execute();

        return $qb->fetchBool();
    }

    public function unfollowUser(string $authorId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_following')
            ->where('authorId = ?', [$authorId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function unfollowAllUsers(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_following')
            ->where('authorId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeAllFollowers(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_following')
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function checkFollow(string $authorId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['followId'])
            ->from('user_following')
            ->where('authorId = ?', [$authorId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        if($qb->fetch('followId') !== null) {
            return true;
        } else {
            return false;
        }
    }

    public function getFollowersForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_following')
            ->where('userId = ?', [$userId])
            ->execute();

        $follows = [];
        while($row = $qb->fetchAssoc()) {
            $follows[] = UserFollowEntity::createEntityFromDbRow($row);
        }

        return $follows;
    }

    public function getFollowersForUserWithOffset(string $userId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_following')
            ->where('userId = ?', [$userId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $follows = [];
        while($row = $qb->fetchAssoc()) {
            $follows[] = UserFollowEntity::createEntityFromDbRow($row);
        }

        return $follows;
    }

    public function getFollowsForUser(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_following')
            ->where('authorId = ?', [$userId])
            ->execute();

        $follows = [];
        while($row = $qb->fetchAssoc()) {
            $follows[] = UserFollowEntity::createEntityFromDbRow($row);
        }

        return $follows;
    }

    public function getFollowsForUserWithOffset(string $userId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_following')
            ->where('authorId = ?', [$userId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $follows = [];
        while($row = $qb->fetchAssoc()) {
            $follows[] = UserFollowEntity::createEntityFromDbRow($row);
        }

        return $follows;
    }
}

?>