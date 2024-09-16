<?php

namespace App\Entities;

use App\Exceptions\TypeException;

class BannedWordEntity extends AEntity {
    private string $id;
    private string $authorId;
    private string $text;
    private string $dateCreated;

    public function __construct(string $id, string $authorId, string $text, string $dateCreated) {
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
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['wordId' => 'string', 'authorId' => 'string', 'word' => 'string', 'dateCreated' => 'string']);
        
        return new self($row->wordId, $row->authorId, $row->word, $row->dateCreated);
    }
}

?>