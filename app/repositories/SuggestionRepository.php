<?php

namespace App\Repositories;

use App\Constants\SuggestionStatus;
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

    public function getOpenSuggestionCount() {
        $statuses = [SuggestionStatus::OPEN, SuggestionStatus::MORE_INFORMATION_NEEDED, SuggestionStatus::PLANNED];

        return $this->getSuggestionCountByStatuses($statuses);
    }

    public function getSuggestionCountByStatuses(array $statuses) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(suggestionId) AS cnt'])
            ->from('user_suggestions')
            ->where($qb->getColumnInValues('status', $statuses))
            ->execute();

        return $qb->fetch('cnt');
    }
}

?>