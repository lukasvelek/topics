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

    public function __construct(int $commentId, int $postId, int $authorId, string $text, string $dateCreated, int $likes, ?int $parentCommentId) {
        $this->commentId = $commentId;
        $this->postId = $postId;
        $this->authorId = $authorId;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
        $this->likes = $likes;
        $this->parentCommentId = $parentCommentId;
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

    public function getParentCommentId() {
        return $this->parentCommentId;
    }

    public static function createEntityFromDbRow(mixed $row) {
        $parentCommentId = null;

        if(isset($row['parentCommentId'])) {
            $parentCommentId = $row['parentCommentId'];
        }

        return new self($row['commentId'], $row['postId'], $row['authorId'], $row['commentText'], $row['dateCreated'], $row['likes'], $parentCommentId);
    }
}

?>