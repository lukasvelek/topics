<?php

namespace App\Entities;

class GroupMembershipEntity extends AEntity {
    private string $id;
    private int $groupId;
    private string $userId;
    private string $dateCreated;

    public function __construct(string $id, int $groupId, string $userId, string $dateCreated) {
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

        $row = self::createRow($row);
        self::checkTypes($row, ['membershipId' => 'string', 'groupId' => 'int', 'userId' => 'string', 'dateCreated' => 'string']);

        return new self($row->membershipId, $row->groupId, $row->userId, $row->dateCreated);
    }
}

?>