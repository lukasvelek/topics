<?php

namespace App\Entities;

class PostEntity implements ICreatableFromRow {
    private int $postId;
    private int $topicId;
    private int $authorId;
    private string $title;
    private string $text;
    private string $dateCreated;
    private int $likes;
    private bool $isDeleted;
    private ?string $dateDeleted;

    public function __construct(int $postId, int $topicId, int $authorId, string $title, string $text, string $dateCreated, int $likes, bool $isDeleted, ?string $dateDeleted) {
        $this->postId = $postId;
        $this->topicId = $topicId;
        $this->authorId = $authorId;
        $this->title = $title;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
        $this->likes = $likes;
        $this->isDeleted = $isDeleted;
        $this->dateDeleted = $dateDeleted;
    }

    public function getId() {
        return $this->postId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getText() {
        return $this->text;
    }

    public function getShortenedText(int $length = 32) {
        if(strlen($this->text) > $length) {
            return substr($this->text, 0, $length) . '...';
        } else {
            return $this->getText();
        }
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getLikes() {
        return $this->likes;
    }

    public function isDeleted() {
        return $this->isDeleted;
    }
    
    public function getDateDeleted() {
        return $this->dateDeleted;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['postId'], $row['topicId'], $row['authorId'], $row['title'], $row['description'], $row['dateCreated'], $row['likes'], $row['isDeleted'], $row['dateDeleted']);
    }
}

?>