<?php

namespace App\Entities;

class PostEntity extends AEntity {
    private string $postId;
    private string $topicId;
    private string $authorId;
    private string $title;
    private string $text;
    private string $dateCreated;
    private int $likes;
    private bool $isDeleted;
    private ?string $dateDeleted;
    private string $tag;
    private string $dateAvailable;
    private bool $isSuggestable;
    private bool $isScheduled;

    private bool $isPinned;

    public function __construct(string $postId, string $topicId, string $authorId, string $title, string $text, string $dateCreated, int $likes, bool $isDeleted, ?string $dateDeleted, string $tag, string $dateAvailable, bool $isSuggestable, bool $isScheduled) {
        $this->postId = $postId;
        $this->topicId = $topicId;
        $this->authorId = $authorId;
        $this->title = $title;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
        $this->likes = $likes;
        $this->isDeleted = $isDeleted;
        $this->dateDeleted = $dateDeleted;
        $this->tag = $tag;
        $this->dateAvailable = $dateAvailable;
        $this->isSuggestable = $isSuggestable;
        $this->isScheduled = $isScheduled;
        
        $this->isPinned = false;
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

    public function getTag() {
        return $this->tag;
    }

    public function getDateAvailable() {
        return $this->dateAvailable;
    }

    public function isSuggestable() {
        return $this->isSuggestable;
    }

    public function isPinned() {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned = true) {
        $this->isPinned = $isPinned;
    }

    public function isScheduled() {
        return $this->isScheduled;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['postId' => 'string', 'topicId' => 'string', 'authorId' => 'string', 'title' => 'string', 'description' => 'string', 'dateCreated' => 'string',
                                'likes' => 'int', 'isDeleted' => 'bool', 'dateDeleted' => '?string', 'tag' => 'string', 'dateAvailable' => 'string', 'isSuggestable' => 'bool',
                                'isScheduled' => 'bool']);

        return new self($row->postId, $row->topicId, $row->authorId, $row->title, $row->description, $row->dateCreated, $row->likes, $row->isDeleted, $row->dateDeleted, $row->tag, $row->dateAvailable, $row->isSuggestable, $row->isScheduled);
    }
}

?>