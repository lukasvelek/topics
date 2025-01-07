<?php

namespace App\Repositories;

use App\Core\Caching\CacheNames;
use App\Core\Caching\Cache;
use App\Core\DatabaseConnection;
use App\Entities\UserEntity;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

class UserRepository extends ARepository {
    private Cache $userCache;
    private Cache $userUsername2IdCache;

    public function __construct(DatabaseConnection $conn, Logger $logger) {
        parent::__construct($conn, $logger);

        $this->userCache = $this->cacheFactory->getCache(CacheNames::USERS);
        $this->userUsername2IdCache = $this->cacheFactory->getCache(CacheNames::USERS_USERNAME_TO_ID_MAPPING);
    }

    public function getUserById(string $id): UserEntity|null {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('userId = ?', [$id]);

        $entity = $this->userCache->load($id, function() use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = UserEntity::createEntityFromDbRow($row);

            return $entity;
        });

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

    public function getUserByEmail(string $email) {
        $qb = $this->getUserByEmailForAuthentication($email);

        return UserEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getUserByEmailForAuthentication(string $email) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('email = ?', [$email])
            ->execute();

        return $qb;
    }

    public function saveLoginHash(string $userId, string $hash) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->set(['loginHash' => $hash])
            ->where('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getLoginHashForUserId(string $userId) {
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

    public function getUserByUsername(string $username): UserEntity|null {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['userId'])
            ->from('users')
            ->where('username = ?', [$username]);

        $userId = $this->userUsername2IdCache->load($username, function() use ($qb) {
            $qb->execute();

            return $qb->fetch('userId');
        });

        if($userId === null) {
            return $userId;
        }

        return $this->getUserById($userId);
    }

    public function getUsersCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(userId) AS cnt'])
            ->from('users')
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getUsersForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        return $this->createUsersArrayFromQb($qb);
    }

    public function composeQueryForUsers() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users');

        return $qb;
    }

    public function updateUser(string $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('users')
            ->set($data)
            ->where('userId = ?', [$id])
            ->execute();

        return $qb->fetchBool();
    }

    public function getUsersByIdBulk(array $ids, bool $idAsKey = false, bool $returnUsernameAsValue = false) {
        $users = [];

        foreach($ids as $id) {
            $result = $this->getUserById($id);

            if($result !== null) {
                if($returnUsernameAsValue) {
                    $result = $result->getUsername();
                }
                
                if($idAsKey) {
                    $users[$id] = $result;
                } else {
                    $users[] = $result;
                }
            }
        }

        return $users;
    }

    public function searchUsersByUsername(string $username) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('users')
            ->where('username LIKE ?', ['%' . $username . '%'])
            ->andWhere('username <> ?', ['service_user'])
            ->execute();

        return $this->createUsersArrayFromQb($qb);
    }

    public function composeStandardQuery(string $username, string $method) {
        $qb = $this->qb(__METHOD__ . ' from ' . $method);

        $qb ->select(['*'])
            ->from('users')
            ->where('username LIKE ?', ['%' . $username . '%'])
            ->andWhere('username <> ?', ['service_user']);

        return $qb;
    }

    public function getUsersFromQb(QueryBuilder $qb, bool $isExecuted = false) {
        if(!$isExecuted) {
            $qb->execute();
        }
        return $this->createUsersArrayFromQb($qb);
    }

    public function createNewUser(string $id, string $username, string $password, ?string $email, bool $isAdmin) {
        $qb = $this->qb(__METHOD__);

        $keys = ['userId', 'username', 'password', 'isAdmin'];
        $values = [$id, $username, $password, $isAdmin];

        if($email !== null) {
            $keys[] = 'email';
            $values[] = $email;
        }

        $qb ->insert('users', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    private function createUsersArrayFromQb(QueryBuilder $qb) {
        $users = [];
        while($row = $qb->fetchAssoc()) {
            $users[] = UserEntity::createEntityFromDbRow($row);
        }

        return $users;
    }

    public function insertNewForgottenPasswordEntry(string $linkId, string $userId, string $dateExpire) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_forgotten_password_links', ['linkId', 'userId', 'dateExpire'])
            ->values([$linkId, $userId, $dateExpire])
            ->execute();

        return $qb->fetchBool();
    }

    public function getForgottenPasswordRequestById(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_forgotten_password_links')
            ->where('linkId = ?', [$id])
            ->execute();

        return $qb->fetch();
    }

    public function updateRequest(string $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_forgotten_password_links')
            ->set($data)
            ->where('linkId = ?', [$id])
            ->execute();

        return $qb->fetchBool();
    }
}

?>