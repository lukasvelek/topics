<?php

namespace App\Entities;

class PostCommentEntity extends AEntity {
    private string $commentId;
    private string $postId;
    private string $authorId;
    private string $text;
    private string $dateCreated;
    private int $likes;
    private ?string $parentCommentId;
    private bool $isDeleted;
    private ?string $dateDeleted;

    public function __construct(string $commentId, string $postId, string $authorId, string $text, string $dateCreated, int $likes, ?string $parentCommentId, bool $isDeleted, ?string $dateDeleted) {
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

        $row = self::createRow($row);
        self::checkTypes($row, ['commentId' => 'string', 'postId' => 'string', 'authorId' => 'string', 'text' => 'string', 'dateCreated' => 'string', 'likes' => 'int', 'parentCommentId' => '?string', 'isDeleted' => 'bool', 'dateDeleted' => '?string']);

        return new self($row->commentId, $row->postId, $row->authorId, $row->commentText, $row->dateCreated, $row->likes, $row->parentCommentId, $row->isDeleted, $row->dateDeleted);
    }
}

?>