<?php

namespace App\Entities;

class TopicMemberEntity implements ICreatableFromRow {
    private string $id;
    private string $userId;
    private string $topicId;
    private int $role;

    public function __construct(string $id, string $userId, string $topicId, int $role) {
        $this->id = $id;
        $this->userId = $userId;
        $this->topicId = $topicId;
        $this->role = $role;
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
    
    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        
        return new self($row['membershipId'], $row['userId'], $row['topicId'], $row['role']);
    }
}

?>