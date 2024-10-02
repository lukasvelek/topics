<?php

namespace App\Entities;

class TopicBroadcastChannelSubscriberEntity extends AEntity {
    private string $subscribeId;
    private string $channelId;
    private string $userId;
    private string $dateCreated;

    public function __construct(string $subscribeId, string $channelId, string $userId, string $dateCreated) {
        $this->subscribeId = $subscribeId;
        $this->channelId = $channelId;
        $this->userId = $userId;
        $this->dateCreated = $dateCreated;
    }

    public function getSubscribeId() {
        return $this->subscribeId;
    }

    public function getChannelId() {
        return $this->channelId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) return null;
        return new self($row['subscribeId'], $row['channelId'], $row['userId'], $row['dateCreated']);
    }
}

?>