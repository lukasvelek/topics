<?php

namespace App\Entities;

class BannedWordEntity implements ICreatableFromRow {
    private int $id;
    private int $authorId;
    private string $text;
    private string $dateCreated;

    public function __construct(int $id, int $authorId, string $text, string $dateCreated) {
        $this->id = $id;
        $this->authorId = $authorId;
        $this->text = $text;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
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
        return new self($row['wordId'], $row['authorId'], $row['word'], $row['dateCreated']);
    }
}

?>