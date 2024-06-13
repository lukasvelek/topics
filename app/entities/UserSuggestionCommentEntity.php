<?php

namespace App\Entities;

class UserSuggestionCommentEntity implements ICreatableFromRow {
    private int $id;
    private int $suggestionId;
    private int $userId;
    private string $text;
    private bool $adminOnly;
    private string $dateCreated;
    private bool $statusChange;

    public function __construct(int $id, int $suggestionId, int $userId, string $text, bool $adminOnly, string $dateCreated, bool $statusChange) {
        $this->id = $id;
        $this->suggestionId = $suggestionId;
        $this->userId = $userId;
        $this->text = $text;
        $this->adminOnly = $adminOnly;
        $this->dateCreated = $dateCreated;
        $this->statusChange = $statusChange;
    }

    public function getId() {
        return $this->id;
    }

    public function getSuggestionId() {
        return $this->suggestionId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getText() {
        return $this->text;
    }

    public function isAdminOnly() { 
        return $this->adminOnly;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function isStatusChange() {
        return $this->statusChange;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['commentId'], $row['suggestionId'], $row['userId'], $row['commentText'], $row['adminOnly'], $row['dateCreated'], $row['statusChange']);
    }
}

?>