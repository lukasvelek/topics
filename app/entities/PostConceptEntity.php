<?php

namespace App\Entities;

class PostConceptEntity implements ICreatableFromRow {
    private string $conceptId;
    private string $authorId;
    private string $topicId;
    private string $postData;
    private string $dateCreated;
    private ?string $dateUpdated;

    public function __construct(string $conceptId, string $authorId, string $topicId, string $postData, string $dateCreated, ?string $dateUpdated) {
        $this->conceptId = $conceptId;
        $this->authorId = $authorId;
        $this->topicId = $topicId;
        $this->postData = $postData;
        $this->dateCreated = $dateCreated;
        $this->dateUpdated = $dateUpdated;
    }

    public function getConceptId() {
        return $this->conceptId;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getPostData(bool $unserialize = true) {
        if($unserialize) {
            return unserialize($this->postData);
        } else {
            return $this->postData;
        }
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getDateUpdated() {
        return $this->dateUpdated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['conceptId'], $row['authorId'], $row['topicId'], $row['postData'], $row['dateCreated'], $row['dateUpdated']);
    }
}

?>