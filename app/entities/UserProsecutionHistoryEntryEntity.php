<?php

namespace App\Entities;

class UserProsecutionHistoryEntryEntity implements ICreatableFromRow {
    private int $id;
    private int $prosecutionId;
    private int $userId;
    private string $text;
    private string $dateCreated;

    public function __construct(int $id, int $prosecutionId, int $userId, string $text, string $dateCreated) {
        $this->id = $id;
        $this->userId = $userId;
        $this->prosecutionId = $prosecutionId;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }
    
    public function getProsecutionId() {
        return $this->prosecutionId;
    }

    public function getText() {
        return $this->text;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['historyId'], $row['prosecutionId'], $row['userId'], $row['commentText'], $row['dateCreated']);
    }
}

?>