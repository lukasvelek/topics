<?php

namespace App\Entities;

class TopicBannedWordEntity extends AEntity {
    private string $id;
    private string $authorId;
    private ?string $topicId;
    private string $text;
    private string $dateCreated;

    public function __construct(string $id, string $authorId, ?string $topicId, string $text, string $dateCreated) {
        $this->id = $id;
        $this->authorId = $authorId;
        $this->topicId = $topicId;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getTopicId() {
        return $this->topicId;
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
        
        return new self($row['wordId'], $row['authorId'], $row['topicId'], $row['word'], $row['dateCreated']);
    }
}

?>