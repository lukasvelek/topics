<?php

namespace App\Repository;

use App\Core\DatabaseConnection;
use App\Core\HashManager;
use App\Entities\UserFollowEntity;
use App\Logger\Logger;
use App\Repositories\ARepository;

class UserFollowingRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function followUser(int $authorId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $followId = HashManager::createHash(16, false);

        $qb ->insert('user_following', ['authorId', 'userId', 'followId'])
            ->values([$authorId, $userId, $followId])
            ->execute();

        return $qb->fetchBool();
    }

    public function unfollowUser(int $authorId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_following')
            ->where('authorId = ?', [$authorId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function unfollowAllUsers(int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_following')
            ->where('authorId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function removeAllFollowers(int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_following')
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function checkFollow(int $authorId, int $userId) {
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

    public function getFollowersForUser(int $userId) {
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

    public function getFollowersForUserWithOffset(int $userId, int $limit, int $offset) {
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

    public function getFollowsForUser(int $userId) {
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

    public function getFollowsForUserWithOffset(int $userId, int $limit, int $offset) {
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