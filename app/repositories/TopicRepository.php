<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Entities\TopicEntity;
use App\Logger\Logger;

class TopicRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getTopicById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topics')
            ->where('topicId = ?', [$id]);

        $entity = CacheManager::loadCache($id, function () use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = TopicEntity::createEntityFromDbRow($row);

            return $entity;
        }, 'topics');

        return $entity;
    }

    public function getFollowedTopicIdsForUser(int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['topicId'])
            ->from('user_topic_follows')
            ->where('userId = ?', [$userId])
            ->execute();

        $topics = [];
        while($row = $qb->fetchAssoc()) {
            $topics[] = $row['topicId'];
        }

        return $topics;
    }

    public function bulkGetTopicsByIds(array $ids) {
        $entities = [];

        foreach($ids as $id) {
            $entities[] = $this->getTopicById($id);
        }

        return $entities;
    }

    public function getFollowersForTopicId(int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['userId'])
            ->from('user_topic_follows')
            ->where('topicId = ?', [$topicId])
            ->execute();

        $followers = [];
        while($row = $qb->fetchAssoc()) {
            $followers[] = $row['userId'];
        }

        return $followers;
    }

    public function getFollowerCountForTopicId(int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(topicId) AS cnt'])
            ->from('user_topic_follows')
            ->where('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetch('cnt') ?? 0;
    }
}

?>