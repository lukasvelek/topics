<?php

namespace App\Entities;

class GroupMembershipEntity implements ICreatableFromRow {
    private int $id;
    private int $groupId;
    private int $userId;
    private string $dateCreated;

    public function __construct(int $id, int $groupId, int $userId, string $dateCreated) {
        $this->id = $id;
        $this->groupId = $groupId;
        $this->userId = $userId;
        $this->dateCreated = $dateCreated;
    }

    public function getId() {
        return $this->id;
    }

    public function getGroupId() {
        return $this->groupId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['membershipId'], $row['groupId'], $row['userId'], $row['dateCreated']);
    }
}

?>