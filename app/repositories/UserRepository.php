<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Entities\UserEntity;
use App\Logger\Logger;

class UserRepository extends ARepository {
    public function __construct(DatabaseConnection $conn, Logger $logger) {
        parent::__construct($conn, $logger);
    }

    public function getUserById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('userId = ?', [$id]);

        $entity = CacheManager::loadCache($id, function () use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = UserEntity::createEntity($row);

            return $entity;
        }, 'users');

        return $entity;
    }

    public function getUserForAuthentication(string $username) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('username = ?', [$username])
            ->execute();

        return $qb;
    }

    public function saveLoginHash(int $userId, string $hash) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->set(['loginHash' => $hash])
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetch();
    }

    public function getLoginHashForUserId(int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['loginHash'])
            ->from('users')
            ->where('userId = ?', [$userId])
            ->execute();

        $loginHash = null;
        while($row = $qb->fetchAssoc()) {
            $loginHash = $row['loginHash'];
        }
        
        return $loginHash;
    }
}

?>