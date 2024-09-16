<?php

namespace App\Entities;

class EmailEntity extends AEntity {
    private string $id;
    private string $recipient;
    private string $title;
    private string $content;
    private string $dateCreated;

    public function __construct(string $id, string $recipient, string $title, string $content, string $dateCreated) {
        $this->id = $id;
        $this->recipient = $recipient;
        $this->title = $title;
        $this->content = $content;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getRecipient() {
        return $this->recipient;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getContent() {
        return $this->content;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['mailId'], $row['recipient'], $row['title'], $row['content'], $row['dateCreated']);
    }
}

?>