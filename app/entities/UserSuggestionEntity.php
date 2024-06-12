<?php

namespace App\Entities;

class UserSuggestionEntity implements ICreatableFromRow {
    private int $id;
    private int $userId;
    private string $title;
    private string $text;
    private string $category;
    private int $status;
    private string $dateCreated;

    public function __construct(int $id, int $userId, string $title, string $text, string $category, int $status, string $dateCreated) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->text = $text;
        $this->category = $category;
        $this->status = $status;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
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

    public function getCategory() {
        return $this->category;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['suggestionId'], $row['userId'], $row['title'], $row['description'], $row['category'], $row['status'], $row['dateCreated']);
    }
}

?>