<?php

namespace App\Entities;

class PostImageFileEntity implements ICreatableFromRow {
    private string $id;
    private int $userId;
    private int $postId;
    private string $filename;
    private string $filepath;
    private string $dateCreated;

    public function __construct(string $id, int $userId, int $postId, string $filename, string $filepath, string $dateCreated) {
        $this->id = $id;
        $this->userId = $userId;
        $this->postId = $postId;
        $this->filename = $filename;
        $this->filepath = $filepath;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getPostId() {
        return $this->postId;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function getFilepath() {
        return $this->filepath;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['uploadId'], $row['userId'], $row['postId'], $row['filename'], $row['filepath'], $row['dateCreated']);
    }
}

?>