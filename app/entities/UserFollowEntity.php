<?php

namespace App\Entities;

class UserFollowEntity extends AEntity {
    private string $id;
    private string $authorId;
    private string $userId;
    private string $dateCreated;

    public function __construct(string $id, string $authorId, string $userId, string $dateCreated) {
        $this->id = $id;
        $this->authorId = $authorId;
        $this->userId = $userId;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['followId'], $row['authorId'], $row['userId'], $row['dateCreated']);
    }
}

?>