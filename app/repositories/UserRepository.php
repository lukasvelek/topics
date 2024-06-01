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
            ->where('id_user = ?', [$id]);

        $entity = CacheManager::loadCache($id, function () use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = UserEntity::createEntity($row);

            return $entity;
        }, 'users');

        return $entity;
    }
}

?>