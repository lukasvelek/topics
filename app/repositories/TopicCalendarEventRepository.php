<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class TopicCalendarEventRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createEvent(string $eventId, string $userId, string $title, string $description, string $dateFrom, string $dateTo) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_calendar_user_events', ['eventId', 'userId', 'title', 'description', 'dateFrom', 'dateTo'])
            ->values([$eventId, $userId, $title, $description, $dateFrom, $dateTo])
            ->execute();

        return $qb->fetchBool();
    }

    public function getEventsForTopicIdForDateRange(string $topicId, string $dateFrom, string $dateTo) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_calendar_user_events')
            ->where('topicId = ?', [$topicId])
            ->andWhere('dateFrom >= ?', [$dateFrom])
            ->andWhere('dateTo <= ?', [$dateTo])
            ->execute();

        $entities = [];
    }
}

?>