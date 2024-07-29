<?php

namespace App\Entities;

class UserActionEntity {
    public const TYPE_TOPIC = 1;
    public const TYPE_POST = 2;
    public const TYPE_POST_COMMENT = 3;
    public const TYPE_POLL = 4;
    public const TYPE_POLL_VOTE = 5;

    private int $id;
    private int $type;
    private string $dateCreated;

    public function __construct(int $id, int $type, string $dateCreated) {
        $this->id = $id;
        $this->type = $type;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getType() {
        return $this->type;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }
}

?>