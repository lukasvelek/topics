<?php

namespace App\Repositories;

use App\Constants\ReportEntityType;
use App\Constants\ReportStatus;
use App\Core\DatabaseConnection;
use App\Entities\ReportEntity;
use App\Logger\Logger;
use App\Managers\EntityManager;

class ReportRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewReport(string $userId, string $entityId, int $entityType, int $category, string $description) {
        $qb = $this->qb(__METHOD__);

        $reportId = $this->createEntityId(EntityManager::REPORTS);

        $qb ->insert('reports', ['reportId', 'userId', 'entityId', 'entityType', 'category', 'description'])
            ->values([$reportId, $userId, $entityId, $entityType, $category, $description])
            ->execute();

        return $qb->fetch();
    }

    public function createTopicReport(string $userId, string $topicId, int $category, string $description) {
        return $this->createNewReport($userId, $topicId, ReportEntityType::TOPIC, $category, $description);
    }

    public function createPostReport(string $userId, string $postId, int $category, string $description) {
        return $this->createNewReport($userId, $postId, ReportEntityType::POST, $category, $description);
    }

    public function createCommentReport(string $userId, string $commentId, int $category, string $description) {
        return $this->createNewReport($userId, $commentId, ReportEntityType::COMMENT, $category, $description);
    }

    public function createUserReport(string $authorId, string $userId, int $category, string $description) {
        return $this->createNewReport($authorId, $userId, ReportEntityType::USER, $category, $description);
    }

    public function getOpenReportsForList(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('status = ?', [ReportStatus::OPEN]);

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[] = ReportEntity::createEntityFromDbRow($row);
        }

        return $reports;
    }

    public function getOpenReportsForListFilterUser(string $userId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('status = ?', [ReportStatus::OPEN])
            ->andWhere('userId = ?', [$userId]);

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[] = ReportEntity::createEntityFromDbRow($row);
        }

        return $reports;
    }

    public function getOpenReportsForListFilterCategory(int $category, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('status = ?', [ReportStatus::OPEN])
            ->andWhere('category = ?', [$category]);

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[] = ReportEntity::createEntityFromDbRow($row);
        }

        return $reports;
    }

    public function getReportsForListFilterStatus(int $status, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('status = ?', [$status]);

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[] = ReportEntity::createEntityFromDbRow($row);
        }

        return $reports;
    }

    public function getReportById(string $reportId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('reportId = ?', [$reportId])
            ->execute();

        return ReportEntity::createEntityFromDbRow($qb->fetch());
    }

    public function updateReport(string $reportId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('reports')
            ->set($data)
            ->where('reportId = ?', [$reportId])
            ->execute();

        return $qb->fetch();
    }

    public function updateRelevantReports(string $reportId, int $entityType, string $entityId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('reports')
            ->set($data)
            ->where($this->xb() ->lb()
                                    ->where('reportId <> ?', [$reportId])
                                ->rb()
                                ->and()
                                ->lb()
                                    ->where('entityType = ?', [$entityType])
                                    ->andWhere('entityId = ?', [$entityId])
                                ->rb()
                                ->build())
            ->execute();

        return $qb->fetch();
    }

    public function getRelevantReports(string $reportId) {
        $report = $this->getReportById($reportId);

        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where($this->xb() ->lb()
                                    ->where('reportId <> ?', [$reportId])
                                ->rb()
                                ->and()
                                ->lb()
                                    ->where('entityType = ?', [$report->getEntityType()])
                                    ->andWhere('entityId = ?', [$report->getEntityId()])
                                ->rb()
                                ->build())
            ->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[] = ReportEntity::createEntityFromDbRow($row);
        }

        return $reports;
    }

    public function getReportByCategory(string $entityId, string $entityType) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('entityType = ?', [$entityType])
            ->andWhere('entityId = ?', [$entityId])
            ->execute();

        return ReportEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getAllReports() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[] = ReportEntity::createEntityFromDbRow($row);
        }
    
        return $reports;
    }
    
    public function getReportCountByStatuses(array $statuses = []) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(reportId) AS cnt'])
            ->from('reports');

        if(!empty($statuses)) {
            $qb->where($qb->getColumnInValues('status', $statuses));
        }

        $qb->execute();

        return $qb->fetch('cnt');
    }

    public function getUsersInReports() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['userId'])
            ->from('reports')
            ->where('status = 1')
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            if(!in_array($row['userId'], $users)) {
                $users[] = $row['userId'];
            }
        }

        return $users;
    }
}

?>