<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class UserRegistrationRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function insertNewConfirmationEntry(string $registrationId, string $userId, string $link, string $dateExpire) {
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

    public function getInactiveOrExpiredRegistrations(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['registrationId'])
            ->from('user_registration_links')
            ->where('isActive = 0')
            ->orWhere('dateExpire < current_timestamp()');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['registrationId'];
        }

        return $ids;
    }

    public function deleteRegistration(string $registrationId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_registration_links')
            ->where('registrationId = ?', [$registrationId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>