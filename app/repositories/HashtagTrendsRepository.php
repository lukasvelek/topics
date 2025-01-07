<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class HashtagTrendsRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewEntry(string $entryId, string $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('hashtag_trends', ['entryId', 'data'])
            ->values([$entryId, $data])
            ->execute();

        return $qb->fetchBool();
    }

    public function getLatestHashtagTrendsEntry() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('hashtag_trends')
            ->orderBy('dateCreated', 'DESC')
            ->limit(1)
            ->execute();

        return $qb->fetch();
    }
}

?>