<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

/**
 * VisibilityAuthorizator allows to check if given user is allowed to view certain things
 * 
 * @author Lukas Velek
 */
class VisibilityAuthorizator extends AAuthorizator {
    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     * @param GroupRepository $groupRepository GroupRepository instance
     * @param UserRepository $userRepository UserRepository instance
     */
    public function __construct(DatabaseConnection $db, Logger $logger, GroupRepository $groupRepository, UserRepository $userRepository) {
        parent::__construct($db, $logger, $groupRepository, $userRepository);
    }

    /**
     * Checks if given user is allowed to view deleted posts
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to view or false if not
     */
    public function canViewDeletedPost(string $userId) {
        return $this->commonContentManagement($userId);
    }

    /**
     * Checks if given user is allowed to view deleted topics
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to view or false if not
     */
    public function canViewDeletedTopic(string $userId) {
        return $this->commonContentManagement($userId);
    }

    /**
     * Common method used to check if user is member of Content manager administrator group or Report user prosecution administrator group or Superadministrator
     * 
     * @return bool True if user member or false if not
     */
    private function commonContentManagement(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) &&
           !$this->isUserMemberOfGroup($userId, AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR) &&
           !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to view private topics
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to view or false if not
     */
    public function canViewPrivateTopic(string $userId) {
        return $this->commonContentManagement($userId);
    }
}

?>