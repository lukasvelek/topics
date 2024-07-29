<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicEntity;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

class TopicRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getTopicById(int $id): TopicEntity|null {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topics')
            ->where('topicId = ?', [$id]);

        $entity = $this->cache->loadCache($id, function () use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = TopicEntity::createEntityFromDbRow($row);

            return $entity;
        }, 'topics', __METHOD__);

        return $entity;
    }

    public function bulkGetTopicsByIds(array $ids, bool $idAsKey = false) {
        $entities = [];

        foreach($ids as $id) {
            if($idAsKey) {
                $entities[$id] = $this->getTopicById($id);
            } else {
                $entities[] = $this->getTopicById($id);
            }
        }

        return $entities;
    }

    public function composeQueryForTopicsSearch(string $query) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topics')
            ->where('(title LIKE ?', ['%' . $query . '%'])
            ->orWhere('description LIKE ?)', ['%' . $query . '%'])
            ->andWhere('(isDeleted = 0)');

        return $qb;
    }

    public function createNewTopic(string $title, string $description, string $tags, bool $isPrivate, string $rawTags) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topics', ['title', 'description', 'tags', 'isPrivate', 'rawTags'])
            ->values([$title, $description, $tags, $isPrivate, $rawTags])
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

    public function getTopicCount(bool $notDeletedOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(topicId) AS cnt'])
            ->from('topics');

        if($notDeletedOnly) {
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

        $this->applyGridValuesToQb($qb, $limit, $offset);

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
            ->andWhere('isDeleted = 0')
            ->execute();

        return $this->createTopicsArrayFromQb($qb);
    }

    public function getPrivateTopics(bool $notDeletedOnly = true) {
        $qb = $this->composeQueryForTopics();

        if($notDeletedOnly) {
            $qb->andWhere('isDeleted = 0');
        }

        $qb->andWhere('isPrivate = 1')
            ->execute();

        return $this->createTopicsArrayFromQb($qb);
    }

    public function searchTags(string $query, array $topicIdsOnly) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['rawTags'])
            ->from('topics')
            ->where('rawTags LIKE ?', ['%' . $query . '%'])
            ->andWhere($qb->getColumnInValues('topicId', $topicIdsOnly))
            ->execute();

        $tags = [];
        while($row = $qb->fetchAssoc()) {
            $topicTags = explode(',', $row['rawTags']);

            foreach($topicTags as $tt) {
                if(str_contains($tt, $query)) {
                    $tags[] = $tt;
                }
            }
        }

        return $tags;
    }

    public function getTopicsWithTag(string $tag) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->select(['*'])
            ->from('topics')
            ->where('rawTags LIKE ?', ['%' . $tag . '%'])
            ->execute();

        return $this->createTopicsArrayFromQb($qb);
    }
}

?>