<?php

namespace App\Entities;

class TopicPollChoiceEntity implements ICreatableFromRow {
    private int $id;
    private int $pollId;
    private int $userId;
    private int $choice;
    private string $dateCreated;

    public function __construct(int $id, int $pollId, int $userId, int $choice, string $dateCreated) {
        $this->id = $id;
        $this->pollId = $pollId;
        $this->userId = $userId;
        $this->choice = $choice;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getPollId() {
        return $this->pollId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getChoice() {
        return $this->choice;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['responseId'], $row['pollId'], $row['userId'], $row['choice'], $row['dateCreated']);
    }
}

?>