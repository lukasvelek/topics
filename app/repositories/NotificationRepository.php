<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class NotificationRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNotification(string $notificationId, int $userId, int $type, string $title, string $message) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('notifications', ['notificationId', 'userId', 'type', 'title', 'message'])
            ->values([$notificationId, $userId, $type, $title, $message])
            ->execute();

        return $qb->fetchBool();
    }
}

?>