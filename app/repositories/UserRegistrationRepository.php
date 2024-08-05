<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class UserRegistrationRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function insertNewConfirmationEntry(string $registrationId, int $userId, string $link, string $dateExpire) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_registration_links', ['registrationId', 'userId', 'link', 'dateExpire', 'isActive'])
            ->values([$registrationId, $userId, $link, $dateExpire, '1'])
            ->execute();

        return $qb->fetchBool();
    }

    public function getRegistrationById(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_registration_links')
            ->where('registrationId = ?', [$id])
            ->execute();

        return $qb->fetch();
    }

    public function deactivateRegistration(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_registration_links')
            ->set(['isActive' => '0'])
            ->where('registrationId = ?', [$id])
            ->execute();

        return $qb->fetchBool();
    }
}

?>