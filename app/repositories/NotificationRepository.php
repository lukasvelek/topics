<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Entities\NotificationEntity;
use App\Logger\Logger;

class NotificationRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNotification(string $notificationId, string $userId, int $type, string $title, string $message) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('notifications', ['notificationId', 'userId', 'type', 'title', 'message'])
            ->values([$notificationId, $userId, $type, $title, $message])
            ->execute();

        return $qb->fetchBool();
    }

    public function getNotificationsForUser(string $userId, bool $unseenOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('notifications')
            ->where('userId = ?', [$userId]);

        if($unseenOnly) {
            $qb->andWhere('dateSeen IS NULL');
        }

        $qb ->orderBy('dateCreated', 'DESC');

        return $this->cache->loadCache($userId, function() use ($qb) {
            $qb->execute();

            $notifications = [];
            while($row = $qb->fetchAssoc()) {
                $notifications[] = NotificationEntity::createEntityFromDbRow($row);
            }

            return $notifications;
        }, CacheManager::NS_USER_NOTIFICATIONS, __METHOD__, CacheManager::EXPIRATION_MINUTES(1));
    }

    public function updateNotification(string $notificationId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('notifications')
            ->set($data)
            ->where('notificationId = ?', [$notificationId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getSeenNotificationsOlderThanX(string $date) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['notificationId'])
            ->from('notifications')
            ->where('dateSeen < ?', [$date])
            ->execute();
        
        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['notificationId'];
        }

        return $ids;
    }

    public function bulkRemoveNotifications(array $notificationIds) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('notifications')
            ->where($qb->getColumnInValues('notificationId', $notificationIds))
            ->execute();

        return $qb->fetchBool();
    }

    public function getSeenNotificationsOlderThanXSteps(string $date, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['notificationId'])
            ->from('notifications')
            ->where('dateSeen < ?', [$date]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            $ids[] = $row['notificationId'];
        }

        return $ids;
    }
}

?>