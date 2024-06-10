<?php

namespace App\Entities;

class PostCommentEntity implements ICreatableFromRow {
    private int $commentId;
    private int $postId;
    private int $authorId;
    private string $text;
    private string $dateCreated;
    private int $likes;

    public function __construct(int $commentId, int $postId, int $authorId, string $text, string $dateCreated, int $likes) {
        $this->commentId = $commentId;
        $this->postId = $postId;
        $this->authorId = $authorId;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
        $this->likes = $likes;
    }

    public function getId() {
        return $this->commentId;
    }

    public function getPostId() {
        return $this->postId;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getText() {
        return $this->text;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getLikes() {
        return $this->likes;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['commentId'], $row['postId'], $row['authorId'], $row['commentText'], $row['dateCreated'], $row['likes']);
    }
}

?>