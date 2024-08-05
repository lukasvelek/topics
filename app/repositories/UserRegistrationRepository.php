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

        $qb ->insert('user_registration_links', ['registrationId', 'userId', 'link', 'dateExpire'])
            ->values([$registrationId, $userId, $link, $dateExpire])
            ->execute();

        return $qb->fetchBool();
    }
}

?>