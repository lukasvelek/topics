<?php

namespace App\Entities;

class TopicEntity implements ICreatableFromRow {
    private int $topicId;
    private string $title;
    private string $description;
    private string $dateCreated;
    private bool $isDeleted;
    private ?string $dateDeleted;
    private array $tags;

    public function __construct(int $topicId, string $title, string $description, string $dateCreated, bool $isDeleted, ?string $dateDeleted, array $tags) {
        $this->topicId = $topicId;
        $this->title = $title;
        $this->description = $description;
        $this->dateCreated = $dateCreated;
        $this->isDeleted = $isDeleted;
        $this->dateDeleted = $dateDeleted;
        $this->tags = $tags;
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

    public function getDateCreated() {
        return $this->dateCreated;
    }
    
    public function isDeleted() {
        return $this->isDeleted;
    }

    public function getDateDeleted() {
        return $this->dateDeleted;
    }

    public function getTags() {
        return $this->tags;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        $tags = unserialize($row['tags']);
        return new self($row['topicId'], $row['title'], $row['description'], $row['dateCreated'], $row['isDeleted'], $row['dateDeleted'], $tags);
    }
}

?>