<?php

namespace App\Entities;

class UserSuggestionCommentEntity extends AEntity {
    private string $id;
    private string $suggestionId;
    private string $userId;
    private string $text;
    private bool $adminOnly;
    private string $dateCreated;
    private bool $statusChange;

    public function __construct(string $id, string $suggestionId, string $userId, string $text, bool $adminOnly, string $dateCreated, bool $statusChange) {
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
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['commentId' => 'string', 'suggestionId' => 'string', 'userId' => 'userId', 'text' => 'string', 'adminOnly' => 'bool', 'dateCreated' => 'string', 'statusChange' => 'bool']);

        return new self($row->commentId, $row->suggestionId, $row->userId, $row->commentText, $row->adminOnly, $row->dateCreated, $row->statusChange);
    }
}

?>