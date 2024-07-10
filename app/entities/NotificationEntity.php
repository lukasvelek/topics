<?php

namespace App\Entities;

class NotificationEntity implements ICreatableFromRow {
    private string $id;
    private int $authorId;
    private string $title;
    private string $message;
    private string $dateCreated;
    private ?string $dateSeen;

    public function __construct(string $id, int $authorId, string $title, string $message, string $dateCreated, ?string $dateSeen) {
        $this->id = $id;
        $this->authorId = $authorId;
        $this->title = $title;
        $this->message = $message;
        $this->dateCreated = $dateCreated;
        $this->dateSeen = $dateSeen;
    }

    public function getId() {
        return $this->id;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getDateSeen() {
        return $this->dateSeen;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['notificationId'], $row['authorId'], $row['title'], $row['message'], $row['dateCreated'], $row['dateSeen']);
    }
}

?>