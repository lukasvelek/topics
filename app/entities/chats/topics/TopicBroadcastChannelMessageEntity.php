<?php

namespace App\Entities;

class TopicBroadcastChannelMessageEntity extends AEntity {
    private string $messageId;
    private string $channelId;
    private string $authorId;
    private string $message;
    private string $dateCreated;

    public function __construct(string $messageId, string $channelId, string $authorId, string $message, string $dateCreated) {
        $this->messageId = $messageId;
        $this->channelId = $channelId;
        $this->authorId = $authorId;
        $this->message = $message;
        $this->dateCreated = $dateCreated;
    }

    public function getMessageId() {
        return $this->messageId;
    }

    public function getChannelId() {
        return $this->channelId;
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
        if($row === null) return null;
        return new self($row['messageId'], $row['channelId'], $row['authorId'], $row['message'], $row['dateCreated']);
    }
}

?>