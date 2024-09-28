<?php

namespace App\Entities;

class UserChatMessageEntity extends AEntity {
    private string $messageId;
    private string $chatId;
    private string $authorId;
    private string $message;
    private string $dateCreated;

    public function __construct(string $messageId, string $chatId, string $authorId, string $message, string $dateCreated) {
        $this->messageId = $messageId;
        $this->chatId = $chatId;
        $this->authorId = $authorId;
        $this->message = $message;
        $this->dateCreated = $dateCreated;
    }

    public function getMessageId() {
        return $this->messageId;
    }

    public function getChatId() {
        return $this->chatId;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['messageId'], $row['chatId'], $row['authorId'], $row['message'], $row['dateCreated']);
    }
}

?>