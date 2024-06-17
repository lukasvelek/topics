<?php

namespace App\Repositories;

use App\Constants\ReportEntityType;
use App\Constants\ReportStatus;
use App\Core\DatabaseConnection;
use App\Entities\ReportEntity;
use App\Logger\Logger;
use QueryBuilder\ExpressionBuilder;

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

    public function createCommentReport(int $userId, int $commentId, int $category, string $description) {
        return $this->createNewReport($userId, $commentId, ReportEntityType::COMMENT, $category, $description);
    }

    public function createUserReport(int $authorId, int $userId, int $category, string $description) {
        return $this->createNewReport($authorId, $userId, ReportEntityType::USER, $category, $description);
    }

    public function getOpenReportsForList(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('status = ?', [ReportStatus::OPEN]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[] = ReportEntity::createEntityFromDbRow($row);
        }

        return $reports;
    }

    public function getOpenReportsForListFilterUser(int $userId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('status = ?', [ReportStatus::OPEN])
            ->andWhere('userId = ?', [$userId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

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

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

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

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $reports = [];
        while($row = $qb->fetchAssoc()) {
            $reports[] = ReportEntity::createEntityFromDbRow($row);
        }

        return $reports;
    }

    public function getReportById(int $reportId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('reports')
            ->where('reportId = ?', [$reportId])
            ->execute();

        return ReportEntity::createEntityFromDbRow($qb->fetch());
    }

    public function updateReport(int $reportId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('reports')
            ->set($data)
            ->where('reportId = ?', [$reportId])
            ->execute();

        return $qb->fetch();
    }

    public function updateRelevantReports(int $reportId, int $entityType, int $entityId, array $data) {
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

    public function getRelevantReports(int $reportId) {
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
}

?>