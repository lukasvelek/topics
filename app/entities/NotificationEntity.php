<?php

namespace App\Entities;

class NotificationEntity extends AEntity {
    private string $id;
    private string $userId;
    private string $title;
    private string $message;
    private string $dateCreated;
    private ?string $dateSeen;

    public function __construct(string $id, string $userId, string $title, string $message, string $dateCreated, ?string $dateSeen) {
        $this->id = $id;
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
        $this->dateCreated = $dateCreated;
        $this->dateSeen = $dateSeen;
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

        $row = self::createRow($row);
        self::checkTypes($row, ['notificationId' => 'string', 'userId' => 'string', 'title' => 'string', 'message' => 'string', 'dateCreated' => 'string', 'dateSeen' => '?string']);

        return new self($row->notificationId, $row->userId, $row->title, $row->message, $row->dateCreated, $row->dateSeen);
    }
}

?>