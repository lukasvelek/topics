<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

class VisibilityAuthorizator extends AAuthorizator {
    public function __construct(DatabaseConnection $db, Logger $logger, GroupRepository $groupRepository, UserRepository $userRepository) {
        parent::__construct($db, $logger, $groupRepository, $userRepository);
    }

    public function canViewDeletedPost(string $userId) {
        return $this->commonContentManagement($userId);
    }

    public function canViewDeletedTopic(string $userId) {
        return $this->commonContentManagement($userId);
    }

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

    public function canViewPrivateTopic(string $userId) {
        return $this->commonContentManagement($userId);
    }
}

?>