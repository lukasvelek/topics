<?php

namespace App\Repositories;

use App\Constants\SuggestionCategory;
use App\Constants\SuggestionStatus;
use App\Core\DatabaseConnection;
use App\Entities\UserEntity;
use App\Entities\UserSuggestionCommentEntity;
use App\Entities\UserSuggestionEntity;
use App\Logger\Logger;

class SuggestionRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewSuggestion(string $suggestionId, string $userId, string $title, string $description, string $category) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_suggestions', ['suggestionId', 'userId', 'title', 'description', 'category'])
            ->values([$suggestionId, $userId, $title, $description, $category])
            ->execute();
        
        return $qb->fetch();
    }

    public function getOpenSuggestionCount() {
        $statuses = [SuggestionStatus::OPEN, SuggestionStatus::MORE_INFORMATION_NEEDED, SuggestionStatus::PLANNED];

        return $this->getSuggestionCountByStatuses($statuses);
    }

    public function getSuggestionCountByStatuses(array $statuses = []) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(suggestionId) AS cnt'])
            ->from('user_suggestions');

        if(!empty($statuses)) {
            $qb->where($qb->getColumnInValues('status', $statuses));
        }

        $qb->execute();

        return $qb->fetch('cnt');
    }

    public function getSuggestionCountByCategories(array $categories = []) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(suggestionId) AS cnt'])
            ->from('user_suggestions');

        if(!empty($categories)) {
            $qb->where($qb->getColumnInValues('category', $categories));
        }

        $qb->execute();

        return $qb->fetch('cnt');
    }

    public function getOpenSuggestionsForList(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestions')
            ->where($qb->getColumnInValues('status', [SuggestionStatus::OPEN, SuggestionStatus::MORE_INFORMATION_NEEDED, SuggestionStatus::PLANNED]));

        $this->applyGridValuesToQb($qb, $limit, $offset);

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

        $this->applyGridValuesToQb($qb, $limit, $offset);

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

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $suggestions = [];
        while($row = $qb->fetchAssoc()) {
            $suggestions[] = UserSuggestionEntity::createEntityFromDbRow($row);
        }

        return $suggestions;
    }

    public function getOpenSuggestionsForListFilterAuthor(string $userId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestions')
            ->where('userId = ?', [$userId]);

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $suggestions = [];
        while($row = $qb->fetchAssoc()) {
            $suggestions[] = UserSuggestionEntity::createEntityFromDbRow($row);
        }

        return $suggestions;
    }

    public function getSuggestionById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestions')
            ->where('suggestionId = ?', [$id])
            ->execute();

        return UserSuggestionEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getCommentsForSuggestion(string $id, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestion_comments')
            ->where('suggestionId = ?', [$id])
            ->orderBy('dateCreated', 'DESC');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $comments = [];
        while($row = $qb->fetchAssoc()) {
            $comments[] = UserSuggestionCommentEntity::createEntityFromDbRow($row);
        }

        return $comments;
    }

    public function getCommentCountForSuggestion(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(commentId) AS cnt'])
            ->from('user_suggestion_comments')
            ->where('suggestionId = ?', [$id])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function createNewComment(string $userId, string $suggestionId, string $text, bool $adminOnly = false) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('user_suggestion_comments', ['userId', 'suggestionId', 'commentText', 'adminOnly'])
            ->values([$userId, $suggestionId, $text, ($adminOnly ? '1' : '0')])
            ->execute();

        return $qb->fetch();
    }

    public function updateSuggestion(string $suggestionId, string $userId, array $values, UserEntity $user) {
        $this->commentSuggestionUpdates($suggestionId, $userId, $values, $user);

        $qb = $this->qb(__METHOD__);

        $qb ->update('user_suggestions')
            ->set($values)
            ->where('suggestionId = ?', [$suggestionId])
            ->execute();

        return $qb->fetch();
    }

    private function commentSuggestionUpdates(string $suggestionId, string $userId, array $values, UserEntity $user) {
        $suggestion = $this->getSuggestionById($suggestionId);

        $updates = [];
        foreach($values as $k => $v) {
            switch($k) {
                case 'status':
                    $createSpan = function($status) {
                        return '<span style="color: ' . SuggestionStatus::getColorByStatus($status) . '"><u>' . SuggestionStatus::toString($status) . '</u></span>';
                    };

                    $updates[$k] = 'from ' . $createSpan($suggestion->getStatus()) . ' to ' . $createSpan($v);
                    break;

                case 'category':
                    $createSpan = function($category) {
                        return '<span style="color: ' . SuggestionCategory::getColorByKey($category) . '"><u>' . SuggestionCategory::toString($category) . '</u></span>';
                    };

                    $updates[$k] = 'from ' . $createSpan($suggestion->getCategory()) . ' to ' . $createSpan($v);
                    break;
            }
        }

        foreach($updates as $k => $text) {
            $text = 'User <u>' . $user->getUsername() . '</u> changed <u>' . $k . '</u> ' . $text . '.';

            $this->createNewComment($userId, $suggestionId, $text, true);
        }
    }

    public function updateComment(string $id, array $values) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('user_suggestion_comments')
            ->set($values)
            ->where('commentId = ?', [$id])
            ->execute();

        return $qb->fetch();
    }

    public function deleteComment(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('user_suggestion_comments')
            ->where('commentId = ?', [$id])
            ->execute();

        return $qb->fetch();
    }

    public function getAllSuggestions() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('user_suggestions')
            ->execute();

        $suggestions = [];
        while($row = $qb->fetchAssoc()) {
            $suggestions[] = UserSuggestionEntity::createEntityFromDbRow($row);
        }

        return $suggestions;
    }

    public function getUsersInSuggestions() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['userId'])
            ->from('user_suggestions')
            ->where($qb->getColumnInValues('status', [SuggestionStatus::OPEN, SuggestionStatus::PLANNED, SuggestionStatus::MORE_INFORMATION_NEEDED]))
            ->execute();

        $users = [];
        while($row = $qb->fetchAssoc()) {
            if(!in_array($row['userId'], $users)) {
                $users[] = $row['userId'];
            }
        }

        return $users;
    }
}

?>