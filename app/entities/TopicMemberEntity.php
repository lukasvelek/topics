<?php

namespace App\Entities;

class TopicMemberEntity {
    private int $id;
    private int $userId;
    private int $topicId;
    private int $role;

    public function __construct(int $id, int $userId, int $topicId, int $role) {
        $this->id = $id;
        $this->userId = $userId;
        $this->topicId = $topicId;
    }
}

?>