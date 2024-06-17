<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Entities\TopicEntity;
use App\Exceptions\CouldNotFetchLastEntityIdException;
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

    public function searchTopics(string $query) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topics')
            ->where('(title LIKE ?', ['%' . $query . '%'])
            ->orWhere('description LIKE ?)', ['%' . $query . '%'])
            ->andWhere('(isDeleted = 0)')
            ->execute();

        $topics = [];
        while($row = $qb->fetchAssoc()) {
            $topics[] = TopicEntity::createEntityFromDbRow($row);
        }

        return $topics;
    }

    public function createNewTopic(int $managerId, string $title, string $description) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topics', ['title', 'description', 'managerId'])
            ->values([$title, $description, $managerId])
            ->execute();

        return $qb->fetch();
    }

    public function getLastTopicIdForManagerId(int $managerId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['topicId'])
            ->from('topics')
            ->where('managerId = ?', [$managerId])
            ->orderBy('dateCreated', 'DESC')
            ->andWhere('isDeleted = 0')
            ->limit(1)
            ->execute();

        return $qb->fetch('topicId');
    }

    public function tryGetLastTopicIdForManagerId(int $managerId) {
        $result = $this->getLastTopicIdForManagerId($managerId);

        if($result === null) {
            throw new CouldNotFetchLastEntityIdException('topic');
        }

        return $result;
    }

    public function checkFollow(int $userId, int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['followId'])
            ->from('user_topic_follows')
            ->where('userId = ?', [$userId])
            ->andWhere('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetch('followId') !== null;
    }

    public function followTopic(int $userId, int $topicId) {
        if($this->checkFollow($userId, $topicId)) {
            return false;
        }

        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_topic_follows', ['userId', 'topicId'])
            ->values([$userId, $topicId])
            ->execute();

        return $qb->fetch();
    }

    public function unfollowTopic(int $userId, int $topicId) {
        if(!$this->checkFollow($userId, $topicId)) {
            return false;
        }

        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_topic_follows')
            ->where('userId = ?', [$userId])
            ->andWhere('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetch();
    }

    public function getNotFollowedTopics(int $userId, array $followedTopics = []) {
        $qb = $this->qb(__METHOD__);
        
        if(empty($followedTopics)) {
            $followedTopics = $this->getFollowedTopicIdsForUser($userId);
        }

        $qb ->select(['*'])
            ->from('topics')
            ->where($qb->getColumnNotInValues('topicId', $followedTopics))
            ->andWhere('isDeleted = 0')
            ->execute();

        $topics = [];
        while($row = $qb->fetchAssoc()) {
            $topics[] = TopicEntity::createEntityFromDbRow($row);
        }

        return $topics;
    }

    public function updateTopic(int $topicId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topics')
            ->set($data)
            ->where('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetch();
    }
}

?>