<?php

namespace App\Entities;

class TopicEntity implements ICreatableFromRow {
    private int $topicId;
    private string $title;
    private string $description;
    private int $managerId;
    private string $dateCreated;
    private bool $isDeleted;

    public function __construct(int $topicId, string $title, string $description, int $managerId, string $dateCreated, bool $isDeleted) {
        $this->topicId = $topicId;
        $this->title = $title;
        $this->description = $description;
        $this->managerId = $managerId;
        $this->dateCreated = $dateCreated;
        $this->isDeleted = $isDeleted;
    }

    public function getId() {
        return $this->topicId;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getManagerId() {
        return $this->managerId;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }
    
    public function isDeleted() {
        return $this->isDeleted;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['topicId'], $row['title'], $row['description'], $row['managerId'], $row['dateCreated'], $row['isDeleted']);
    }
}

?>