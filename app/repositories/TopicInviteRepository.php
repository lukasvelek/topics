<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicInviteEntity;
use App\Logger\Logger;
use App\Managers\EntityManager;

class TopicInviteRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function composeQueryForInvitesForTopic(string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_invites')
            ->where('topicId = ?', [$topicId]);

        $now = new DateTime();
        $now = $now->getResult();

        $qb->andWhere('dateValid > ?', [$now]);

        return $qb;
    }

    public function getInvitesForGrid(string $topicId, bool $validOnly, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_invites')
            ->where('topicId = ?', [$topicId]);

        if($validOnly) {
            $now = new DateTime();
            $now = $now->getResult();

            $qb->andWhere('dateValid > ?', [$now]);
        }

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $invites = [];
        while($row = $qb->fetchAssoc()) {
            $invites[] = TopicInviteEntity::createEntityFromDbRow($row);
        }

        return $invites;
    }

    public function createInvite(string $topicId, string $userId, string $dateValid) {
        $qb = $this->qb(__METHOD__);

        $inviteId = $this->createEntityId(EntityManager::TOPIC_INVITES);

        $qb ->insert('topic_invites', ['inviteId', 'topicId', 'userId', 'dateValid'])
            ->values([$inviteId, $topicId, $userId, $dateValid])
            ->execute();

        return $qb->fetchBool();
    }

    public function getInviteForTopicAndUser(string $topicId, string $userId) {
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

    public function deleteInvite(string $topicId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('topic_invites')
            ->where('topicId = ?', [$topicId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getInvitesForUserForGrid(string $userId, int $limit, int $offset, bool $validOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_invites')
            ->where('userId = ?', [$userId]);

        if($validOnly) {
            $now = new DateTime();
            $now = $now->getResult();

            $qb->andWhere('dateValid > ?', [$now]);
        }
        
        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $invites = [];
        while($row = $qb->fetchAssoc()) {
            $invites[] = TopicInviteEntity::createEntityFromDbRow($row);
        }

        return $invites;
    }

    public function getAllTopicsInUserInvites(string $userId, bool $validOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['topicId'])
            ->from('topic_invites')
            ->where('userId = ?', [$userId]);

        if($validOnly) {
            $now = new DateTime();
            $now = $now->getResult();

            $qb->andWhere('dateValid > ?', [$now]);
        }

        $qb->execute();

        $topics = [];
        while($row = $qb->fetchAssoc()) {
            $topics[] = $row['topicId'];
        }

        return $topics;
    }
}

?>