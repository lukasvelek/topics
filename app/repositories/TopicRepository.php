<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicEntity;
use App\Exceptions\CouldNotFetchLastEntityIdException;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

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

    public function createNewTopic(string $title, string $description) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topics', ['title', 'description'])
            ->values([$title, $description])
            ->execute();

        return $qb->fetch();
    }

    public function getLastTopicIdForTitle(string $title) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['topicId'])
            ->from('topics')
            ->where('title = ?', [$title])
            ->orderBy('dateCreated', 'DESC')
            ->andWhere('isDeleted = 0')
            ->limit(1)
            ->execute();

        return $qb->fetch('topicId');
    }

    public function checkFollow(int $userId, int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['followId'])
            ->from('user_topic_follows')
            ->where('userId = ?', [$userId])
            ->andWhere('topicId = ?', [$topicId])
            ->execute();

        return ($qb->fetch('followId') !== null);
    }

    public function followTopic(int $userId, int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_topic_follows', ['userId', 'topicId'])
            ->values([$userId, $topicId])
            ->execute();

        return $qb->fetchBool();
    }

    public function unfollowTopic(int $userId, int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_topic_follows')
            ->where('userId = ?', [$userId])
            ->andWhere('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetchBool();
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

    public function removeAllTopicFollows(int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_topic_follows')
            ->where('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetch();
    }

    public function getTopicCount(bool $deletedOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(topicId) AS cnt'])
            ->from('topics');

        if($deletedOnly) {
            $qb->where('isDeleted = 0');
        }

        $qb->execute();

        return $qb->fetch('cnt');
    }

    public function deleteTopic(int $topicId, bool $hide = true) {
        if($hide) {
            $date = new DateTime();
            return $this->updateTopic($topicId, ['isDeleted' => '1', 'dateDeleted' => $date->getResult()]);
        } else {
            $qb = $this->qb(__METHOD__);

            $qb ->delete()
                ->from('topics')
                ->where('topicId = ?', [$topicId])
                ->execute();

            return $qb->fetch();
        }
    }

    public function getDeletedTopicsForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topics')
            ->where('isDeleted = 1');

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        return $this->createTopicsArrayFromQb($qb);
    }

    public function getDeletedTopicCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(topicId) AS cnt'])
            ->from('topics')
            ->where('isDeleted = 1')
            ->execute();

        return $qb->fetch('cnt');
    }

    public function createTopicsArrayFromQb(QueryBuilder $qb) {
        $topics = [];

        while($row = $qb->fetchAssoc()) {
            $topics[] = TopicEntity::createEntityFromDbRow($row);
        }

        return $topics;
    }

    public function composeQueryForTopics() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topics');

        return $qb;
    }
}

?>