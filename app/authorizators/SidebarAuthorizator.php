<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

class SidebarAuthorizator extends AAuthorizator {
    private UserRepository $userRepository;
    private GroupRepository $groupRepository;

    public function __construct(DatabaseConnection $db, Logger $logger, UserRepository $userRepository, GroupRepository $groupRepository) {
        parent::__construct($db, $logger);
        
        $this->userRepository = $userRepository;
        $this->groupRepository = $groupRepository;
    }

    public function canManageUsers(int $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_USER_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    public function canManageUserProsecutions(int $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    public function canManageSystemStatus(int $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_SYSTEM_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    public function canManageSuggestions(int $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_SUGGESTION_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    public function canManageReports(int $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    private function isUserAdmin(int $userId) {
        $user = $this->userRepository->getUserById($userId);

        return $user->isAdmin();
    }

    private function isUserSuperAdministrator(int $userId) {
        return $this->isUserMemberOfGroup($userId, AdministratorGroups::G_SUPERADMINISTRATOR);
    }

    private function isUserMemberOfGroup(int $userId, int $groupId) {
        return CacheManager::loadCache('group_' . $groupId . '_' . $userId, function() use ($userId, $groupId) {
            return $this->groupRepository->isUserMemberOfGroup($userId, $groupId);
        }, 'groupMemberships');
    }
}

?>