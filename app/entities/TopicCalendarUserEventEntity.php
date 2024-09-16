<?php

namespace App\Entities;

class TopicCalendarUserEventEntity extends AEntity {
    private string $id;
    private string $userId;
    private string $topicId;
    private string $title;
    private string $description;
    private string $dateCreated;
    private string $dateFrom;
    private string $dateTo;

    public function __construct(string $id, string $userId, string $topicId, string $title, string $description, string $dateCreated, string $dateFrom, string $dateTo) {
        $this->id = $id;
        $this->userId = $userId;
        $this->topicId = $topicId;
        $this->title = $title;
        $this->description = $description;
        $this->dateCreated = $dateCreated;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getDateFrom() {
        return $this->dateFrom;
    }

    public function getDateTo() {
        return $this->dateTo;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['eventId'], $row['userId'], $row['topicId'], $row['title'], $row['description'], $row['dateCreated'], $row['dateFrom'], $row['dateTo']);
    }
}

?>