<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

/**
 * SidebarAuthorizator allows to check if given user is allowed to see sidebar links
 * 
 * @author Lukas Velek
 */
class SidebarAuthorizator extends AAuthorizator {
    /**
     * Class constructor
     * 
     * @param DatabaseConnection $db DatabaseConnection instance
     * @param Logger $logger Logger instance
     * @param UserRepository $userRepository
     * @param GroupRepository $groupRepository
     */
    public function __construct(DatabaseConnection $db, Logger $logger, UserRepository $userRepository, GroupRepository $groupRepository) {
        parent::__construct($db, $logger, $groupRepository, $userRepository);
    }

    /**
     * Checks if given user is allowed to manage users
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageUsers(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_USER_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage user prosecutions
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageUserProsecutions(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage system status
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageSystemStatus(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_SYSTEM_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage suggestions
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageSuggestions(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_SUGGESTION_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage reports
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageReports(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_REPORT_USER_PROSECUTION_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage deleted content
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageDeletedContent(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage banned words
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageBannedWords(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage post file uploads
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManagePostFileUploads(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_CONTENT_MANAGER_AND_ADMINISTRATOR) && !$this->isUserMemberOfGroup($userId, AdministratorGroups::G_SYSTEM_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage database transactions
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageTransactions(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_SYSTEM_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage grid exports
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageGridExports(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_SYSTEM_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if given user is allowed to manage emails
     * 
     * @param string $userId User ID
     * @return bool True if user is allowed to see this sidebar link or false if not
     */
    public function canManageEmails(string $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_SYSTEM_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }
}

?>