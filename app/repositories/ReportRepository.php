<?php

namespace App\Repositories;

use App\Constants\ReportEntityType;
use App\Core\DatabaseConnection;
use App\Logger\Logger;

class ReportRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewReport(int $userId, int $entityId, int $entityType, int $category, string $description) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('reports', ['userId', 'entityId', 'entityType', 'category', 'description'])
            ->values([$userId, $entityId, $entityType, $category, $description])
            ->execute();

        return $qb->fetch();
    }

    public function createTopicReport(int $userId, int $topicId, int $category, string $description) {
        return $this->createNewReport($userId, $topicId, ReportEntityType::TOPIC, $category, $description);
    }

    public function createPostReport(int $userId, int $postId, int $category, string $description) {
        return $this->createNewReport($userId, $postId, ReportEntityType::POST, $category, $description);
    }
}

?>