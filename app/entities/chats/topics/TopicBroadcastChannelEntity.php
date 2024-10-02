<?php

namespace App\Entities;

class TopicBroadcastChannelEntity extends AEntity {
    private string $channelId;
    private string $topicId;
    private string $dateCreated;
    
    public function __construct(string $channelId, string $topicId, string $dateCreated) {
        $this->channelId = $channelId;
        $this->topicId = $topicId;
        $this->dateCreated = $dateCreated;
    }

    public function getChannelId() {
        return $this->channelId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) return null;
        return new self($row['channelId'], $row['topicId'], $row['dateCreated']);
    }
}

?>