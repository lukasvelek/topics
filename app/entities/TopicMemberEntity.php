<?php

namespace App\Entities;

class TopicMemberEntity extends AEntity {
    private string $id;
    private string $userId;
    private string $topicId;
    private int $role;
    private string $dateCreated;

    public function __construct(string $id, string $userId, string $topicId, int $role, string $dateCreated) {
        $this->id = $id;
        $this->userId = $userId;
        $this->topicId = $topicId;
        $this->role = $role;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getRole() {
        return $this->role;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }
    
    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        
        return new self($row['membershipId'], $row['userId'], $row['topicId'], $row['role'], $row['dateCreated']);
    }
}

?>