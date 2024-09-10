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
}

?>