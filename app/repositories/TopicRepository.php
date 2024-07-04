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

        $entity = $this->cache->loadCache($id, function () use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = TopicEntity::createEntityFromDbRow($row);

            return $entity;
        }, 'topics');

        return $entity;
    }

    public function bulkGetTopicsByIds(array $ids) {
        $entities = [];

        foreach($ids as $id) {
            $entities[] = $this->getTopicById($id);
        }

        return $entities;
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

    public function updateTopic(int $topicId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topics')
            ->set($data)
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

    public function getTopicsExceptFor(array $topicIds) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topics')
            ->where($qb->getColumnNotInValues('topicId', $topicIds))
            ->execute();

        return $this->createTopicsArrayFromQb($qb);
    }
}

?>