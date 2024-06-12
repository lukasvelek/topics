<?php

namespace App\Repositories;

use App\Constants\SuggestionStatus;
use App\Core\DatabaseConnection;
use App\Entities\UserSuggestionEntity;
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

    public function getOpenSuggestionsForList(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestions')
            ->where($qb->getColumnInValues('status', [SuggestionStatus::OPEN, SuggestionStatus::MORE_INFORMATION_NEEDED, SuggestionStatus::PLANNED]));

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $suggestions = [];
        while($row = $qb->fetchAssoc()) {
            $suggestions[] = UserSuggestionEntity::createEntityFromDbRow($row);
        }

        return $suggestions;
    }

    public function getOpenSuggestionsForListFilterCategory(string $category, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestions')
            ->where($qb->getColumnInValues('status', [SuggestionStatus::OPEN, SuggestionStatus::MORE_INFORMATION_NEEDED, SuggestionStatus::PLANNED]))
            ->andWhere('category = ?', [$category]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $suggestions = [];
        while($row = $qb->fetchAssoc()) {
            $suggestions[] = UserSuggestionEntity::createEntityFromDbRow($row);
        }

        return $suggestions;
    }

    public function getSuggestionsForListFilterStatus(int $status, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestions')
            ->where('status = ?', [$status]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $suggestions = [];
        while($row = $qb->fetchAssoc()) {
            $suggestions[] = UserSuggestionEntity::createEntityFromDbRow($row);
        }

        return $suggestions;
    }

    public function getOpenSuggestionsForListFilterAuthor(int $userId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestions')
            ->where('userId = ?', [$userId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $suggestions = [];
        while($row = $qb->fetchAssoc()) {
            $suggestions[] = UserSuggestionEntity::createEntityFromDbRow($row);
        }

        return $suggestions;
    }
}

?>