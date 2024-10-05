<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;
use QueryBuilder\ExpressionBuilder;
use QueryBuilder\QueryBuilder;

/**
 * Abstract class AAuthorizator that contains common methods used in other extending authorizators.
 * 
 * @author Lukas Velek
 */
abstract class AAuthorizator {
    private DatabaseConnection $db;
    protected Logger $logger;
    protected GroupRepository $groupRepository;
    protected UserRepository $userRepository;
    private CacheFactory $cacheFactory;

    private Cache $groupMembershipsCache;

    /**
     * Abstract class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     * @param GroupRepository $groupRepository GroupRepository instance
     * @param UserRepository $userRepository UserRepository instance
     */
    protected function __construct(DatabaseConnection $db, Logger $logger, GroupRepository $groupRepository, UserRepository $userRepository) {
        $this->db = $db;
        $this->logger = $logger;
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
        $this->cacheFactory = new CacheFactory($this->logger->getCfg());

        $this->groupMembershipsCache = $this->cacheFactory->getCache(CacheNames::GROUP_MEMBERSHIPS);
    }

    /**
     * Returns a new instance of QueryBuilder
     * 
     * @param string $method Name of the calling method
     * @return QueryBuilder QueryBuilder instance
     */
    protected function qb(string $method = __METHOD__) {
        return new QueryBuilder($this->db, $this->logger, $method);
    }
    
    /**
     * Returns a new instance of ExpressionBuilder
     * 
     * @return ExpressionBuilder ExpressionBuilder instance
     */
    protected function xb() {
        return new ExpressionBuilder();
    }

    /**
     * Checks if given user is a member of given group
     * 
     * @param string $userId User ID
     * @param int $groupId Group ID
     * @return bool True if user is member or false if not
     */
    protected function isUserMemberOfGroup(string $userId, int $groupId): bool {
        return $this->groupMembershipsCache->load('group_' . $groupId . '_' . $userId, function() use ($userId, $groupId) {
            return $this->groupRepository->isUserMemberOfGroup($userId, $groupId);
        });
    }

    /**
     * Checks if given user is administrator
     * 
     * @param string $userId User ID
     * @return bool True if user is administrator or false if not
     */
    protected function isUserAdmin(string $userId) {
        $user = $this->userRepository->getUserById($userId);

        return $user->isAdmin();
    }

    /**
     * Checks if user is member of the Superadministrators group
     * 
     * @param string $userId User ID
     * @return bool True if user is member or false if not
     */
    protected function isUserSuperAdministrator(string $userId) {
        return $this->isUserMemberOfGroup($userId, AdministratorGroups::G_SUPERADMINISTRATOR);
    }

    /**
     * Checks if user is a beta tester (member of Beta testers group)
     * 
     * @param string $userId User ID
     * @return bool True if user is member or false if not
     */
    public function isUserBetaTester(string $userId) {
        return $this->isUserMemberOfGroup($userId, AdministratorGroups::G_BETA_TESTER);
    }
}

?>