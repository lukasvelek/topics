<?php

namespace App\Authorizators;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\GroupRepository;
use App\Repositories\UserRepository;

class SystemStatusAuthorizator extends AAuthorizator {
    public function __construct(DatabaseConnection $db, Logger $logger, GroupRepository $groupRepository, UserRepository $userRepository) {
        parent::__construct($db, $logger, $groupRepository, $userRepository);
    }

    public function isUserSuperAdministrator(string $userId) {
        return parent::isUserSuperAdministrator($userId);
    }

    public function isUserMemberOfGroup(string $userId, int $groupId): bool {
        return parent::isUserMemberOfGroup($userId, $groupId);
    }
}

?>