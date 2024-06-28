<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\BannedWordEntity;
use App\Logger\Logger;

class ContentRegulationRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getBannedWordsForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('banned_words');
        
        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = BannedWordEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getBannedWordsCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(word) AS cnt'])
            ->from('banned_words')
            ->execute();

        return $qb->fetch('cnt');
    }

    public function createNewBannedWord(string $word, int $authorId) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('banned_words', ['word', 'authorId'])
            ->values([$word, $authorId])
            ->execute();

        return $qb->fetch();
    }

    public function deleteBannedWord(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('banned_words')
            ->where('wordId = ?', [$id])
            ->execute();

        return $qb->fetch();
    }
}

?>