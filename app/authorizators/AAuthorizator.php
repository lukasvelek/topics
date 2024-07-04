<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;
use QueryBuilder\ExpressionBuilder;
use QueryBuilder\QueryBuilder;

abstract class AAuthorizator {
    private DatabaseConnection $db;
    protected Logger $logger;
    protected GroupRepository $groupRepository;
    protected UserRepository $userRepository;

    protected function __construct(DatabaseConnection $db, Logger $logger, GroupRepository $groupRepository, UserRepository $userRepository) {
        $this->db = $db;
        $this->logger = $logger;
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    protected function qb(string $method = __METHOD__) {
        return new QueryBuilder($this->db, $this->logger, $method);
    }
    
    protected function xb() {
        return new ExpressionBuilder();
    }

    protected function isUserMemberOfGroup(int $userId, int $groupId) {
        $cm = new CacheManager($this->logger);
        return $cm->loadCache('group_' . $groupId . '_' . $userId, function() use ($userId, $groupId) {
            return $this->groupRepository->isUserMemberOfGroup($userId, $groupId);
        }, 'groupMemberships');
    }

    protected function isUserAdmin(int $userId) {
        $user = $this->userRepository->getUserById($userId);

        return $user->isAdmin();
    }

    protected function isUserSuperAdministrator(int $userId) {
        return $this->isUserMemberOfGroup($userId, AdministratorGroups::G_SUPERADMINISTRATOR);
    }
}

?>