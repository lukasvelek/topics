<?php

namespace App\Entities;

class PostCommentEntity implements ICreatableFromRow {
    private int $commentId;
    private int $postId;
    private int $authorId;
    private string $text;
    private string $dateCreated;

    public function __construct(int $commentId, int $postId, int $authorId, string $text, string $dateCreated) {
        $this->commentId = $commentId;
        $this->postId = $postId;
        $this->authorId = $authorId;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
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

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['commentId'], $row['postId'], $row['authorId'], $row['commentText'], $row['dateCreated']);
    }
}

?>