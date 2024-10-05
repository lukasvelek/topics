<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\TopicBannedWordEntity;
use App\Logger\Logger;

class TopicContentRegulationRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getBannedWordsForTopicForGrid(string $topicId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_banned_words')
            ->where('topicId = ?', [$topicId]);

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = TopicBannedWordEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getBannedWordById(string $wordId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_banned_words')
            ->where('wordId = ?', [$wordId])
            ->execute();

        return TopicBannedWordEntity::createEntityFromDbRow($qb->fetch());
    }

    public function createNewBannedWord(string $wordId, string $topicId, string $authorId, string $word) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_banned_words', ['wordId', 'topicId', 'authorId', 'word'])
            ->values([$wordId, $topicId, $authorId, $word])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateBannedWord(string $wordId, array $data) {
        $qb = $this->qb(__METHOD__);
        
        $qb ->update('topic_banned_words')
            ->set($data)
            ->where('wordId = ?', [$wordId])
            ->execute();

        return $qb->fetchBool();
    }

    public function deleteBannedWord(string $wordId) {
        return $this->deleteEntryById('topic_banned_words', 'wordId', $wordId);
    }

    public function deleteBannedWordsForTopicId(string $topicId) {
        return $this->deleteEntryById('topic_banned_words', 'topicId', $topicId);
    }
}

?>