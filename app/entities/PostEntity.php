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

    public function __construct(int $postId, int $topicId, int $authorId, string $title, string $text, string $dateCreated, int $likes) {
        $this->postId = $postId;
        $this->topicId = $topicId;
        $this->authorId = $authorId;
        $this->title = $title;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
        $this->likes = $likes;
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
        return substr($this->text, 0, $length);
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getLikes() {
        return $this->likes;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['postId'], $row['topicId'], $row['authorId'], $row['title'], $row['description'], $row['dateCreated'], $row['likes']);
    }
}

?>