<?php

namespace App\Authorizators;

use App\Constants\AdministratorGroups;
use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

class ActionAuthorizator extends AAuthorizator {
    public function __construct(DatabaseConnection $db, Logger $logger, UserRepository $userRepository, GroupRepository $groupRepository) {
        parent::__construct($db, $logger, $groupRepository, $userRepository);
    }

    public function canRemoveMemberFromGroup(int $userId) {
        if(!$this->isUserAdmin($userId)) {
            return false;
        }

        if(!$this->isUserMemberOfGroup($userId, AdministratorGroups::G_USER_ADMINISTRATOR) && !$this->isUserSuperAdministrator($userId)) {
            return false;
        }

        return true;
    }

    public function canAddMemberToGroup(int $userId) {
        return $this->canRemoveMemberFromGroup($userId);
    }
}

?>