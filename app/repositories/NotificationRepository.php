<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\NotificationEntity;
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

    public function getNotificationsForUser(int $userId, bool $unseenOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('notifications')
            ->where('userId = ?', [$userId]);

        if($unseenOnly) {
            $qb->andWhere('dateSeen IS NULL');
        }

        $qb ->orderBy('dateCreated', 'DESC')
            ->execute();

        $notifications = [];
        while($row = $qb->fetchAssoc()) {
            $notifications[] = NotificationEntity::createEntityFromDbRow($row);
        }

        return $notifications;
    }

    public function updateNotification(string $notificationId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('notifications')
            ->set($data)
            ->where('notificationId = ?', [$notificationId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>