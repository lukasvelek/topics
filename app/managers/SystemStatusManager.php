<?php

namespace App\Managers;

use App\Authorizators\SystemStatusAuthorizator;
use App\Constants\AdministratorGroups;
use App\Constants\Systems;
use App\Constants\SystemStatus;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\SystemStatusRepository;

class SystemStatusManager extends AManager {
    private SystemStatusRepository $ssr;
    private SystemStatusAuthorizator $ssa;

    public function __construct(Logger $logger, EntityManager $entityManager, SystemStatusRepository $ssr, SystemStatusAuthorizator $ssa) {
        parent::__construct($logger, $entityManager);

        $this->ssr = $ssr;
        $this->ssa = $ssa;
    }

    private function commonCanUser(string $userId, array $groupsAllowed = [AdministratorGroups::G_SUPERADMINISTRATOR]) {
        $isMember = true;

        foreach($groupsAllowed as $group) {
            if($isMember === false) {
                break;
            }
            $isMember = $this->ssa->isUserMemberOfGroup($userId, $group);
        }

        return $isMember;
    }

    public function canAccessSystem(string $userId) {
        return $this->commonCanUser($userId, [AdministratorGroups::G_SUPERADMINISTRATOR, AdministratorGroups::G_SYSTEM_ADMINISTRATOR]);
    }

    public function isUserSuperAdministrator(string $userId) {
        return $this->ssa->isUserSuperAdministrator($userId);
    }

    public function isSystemOn(string $systemName) {
        $systemStatus = $this->ssr->getSystemStatusByName(Systems::toString($systemName));

        if($systemStatus === null) {
            throw new NonExistingEntityException('System named \'' . $systemName . '\' does not exist.');
        } else {
            if(in_array($systemStatus->getStatus(), [SystemStatus::MAINTENANCE, SystemStatus::OFFLINE])) {
                return false;
            } else {
                return true;
            }
        }
    }
}

?>