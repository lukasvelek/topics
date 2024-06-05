<?php

namespace App\Entities;

class TopicEntity implements ICreatableFromRow {
    private int $topicId;
    private string $title;
    private string $description;
    private int $managerId;
    private string $dateCreated;

    public function __construct(int $topicId, string $title, string $description, int $managerId, string $dateCreated) {
        $this->topicId = $topicId;
        $this->title = $title;
        $this->description = $description;
        $this->managerId = $managerId;
        $this->dateCreated = $dateCreated;
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

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['topicId'], $row['title'], $row['description'], $row['managerId'], $row['dateCreated']);
    }
}

?>