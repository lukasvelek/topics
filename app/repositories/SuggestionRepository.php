<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class SuggestionRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewSuggestion(int $userId, string $title, string $description, string $category) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_suggestions', ['userId', 'title', 'description', 'category'])
            ->values([$userId, $title, $description, $category])
            ->execute();
        
        return $qb->fetch();
    }
}

?>