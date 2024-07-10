<?php

namespace App\Entities;

class TopicInviteEntity implements ICreatableFromRow {
    private int $topicId;
    private int $userId;
    private string $dateCreated;
    private string $dateValid;

    public function __construct(int $topicId, int $userId, string $dateCreated, string $dateValid) {
        $this->topicId = $topicId;
        $this->userId = $userId;
        $this->dateCreated = $dateCreated;
        $this->dateValid = $dateValid;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getDateValid() {
        return $this->dateValid;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['topicId'], $row['userId'], $row['dateCreated'], $row['dateValid']);
    }
}

?>