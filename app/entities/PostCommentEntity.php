<?php

namespace App\Entities;

class PostCommentEntity implements ICreatableFromRow {
    private int $commentId;
    private int $postId;
    private int $authorId;
    private string $text;
    private string $dateCreated;
    private int $likes;
    private ?int $parentCommentId;
    private bool $isDeleted;
    private ?string $dateDeleted;

    public function __construct(int $commentId, int $postId, int $authorId, string $text, string $dateCreated, int $likes, ?int $parentCommentId, bool $isDeleted, ?string $dateDeleted) {
        $this->commentId = $commentId;
        $this->postId = $postId;
        $this->authorId = $authorId;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
        $this->likes = $likes;
        $this->parentCommentId = $parentCommentId;
        $this->isDeleted = $isDeleted;
        $this->dateDeleted = $dateDeleted;
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

    public function getParentCommentId() {
        return $this->parentCommentId;
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

        $parentCommentId = null;

        if(isset($row['parentCommentId'])) {
            $parentCommentId = $row['parentCommentId'];
        }

        return new self($row['commentId'], $row['postId'], $row['authorId'], $row['commentText'], $row['dateCreated'], $row['likes'], $parentCommentId, $row['isDeleted'], $row['dateDeleted']);
    }
}

?>