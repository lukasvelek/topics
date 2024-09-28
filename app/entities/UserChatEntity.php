<?php

namespace App\Entities;

class UserChatEntity extends AEntity {
    private string $chatId;
    private string $user1Id;
    private string $user2Id;
    private string $dateCreated;

    public function __construct(string $chatId, string $user1Id, string $user2Id, string $dateCreated) {
        $this->chatId = $chatId;
        $this->user1Id = $user1Id;
        $this->user2Id = $user2Id;
        $this->dateCreated = $dateCreated;
    }

    public function getChatId() {
        return $this->chatId;
    }

    public function getUser1Id() {
        return $this->user1Id;
    }

    public function getUser2Id() {
        return $this->user2Id;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['chatId'], $row['user1Id'], $row['user2Id'], $row['dateCreated']);
    }
}

?>