<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicInviteEntity;
use App\Logger\Logger;

class TopicInviteRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getInvitesForGrid(int $topicId, bool $validOnly, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_invites')
            ->where('topicId = ?', [$topicId]);

        if($validOnly) {
            $now = new DateTime();
            $now = $now->getResult();

            $qb->andWhere('dateValid > ?', [$now]);
        }

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $invites = [];
        while($row = $qb->fetchAssoc()) {
            $invites[] = TopicInviteEntity::createEntityFromDbRow($row);
        }

        return $invites;
    }

    public function createInvite(int $topicId, int $userId, string $dateValid) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_invites', ['topicId', 'userId', 'dateValid'])
            ->values([$topicId, $userId, $dateValid])
            ->execute();

        return $qb->fetchBool();
    }

    public function getInviteForTopicAndUser(int $topicId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $now = new DateTime();
        $now = $now->getResult();

        $qb ->select(['*'])
            ->from('topic_invites')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->andWhere('dateValid > ?', [$now])
            ->execute();

        return TopicInviteEntity::createEntityFromDbRow($qb->fetch());
    }

    public function deleteInvite(int $topicId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('topic_invites')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }
}

?>