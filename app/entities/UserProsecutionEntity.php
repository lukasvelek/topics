<?php

namespace App\Entities;

class UserProsecutionEntity implements ICreatableFromRow {
    private int $id;
    private int $userId;
    private int $type;
    private string $reason;
    private ?string $startDate;
    private ?string $endDate;

    public function __construct(int $id, int $userId, int $type, string $reason, ?string $startDate, ?string $endDate) {
        $this->id = $id;
        $this->userId = $userId;
        $this->type = $type;
        $this->reason = $reason;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }
    
    public function getType() {
        return $this->type;
    }

    public function getReason() {
        return $this->reason;
    }

    public function getStartDate() {
        return $this->startDate;
    }

    public function getEndDate() {
        return $this->endDate;
    }

    public static function createEntityFromDbRow(mixed $row) {
        return new self($row['prosecutionId'], $row['userId'], $row['type'], $row['reason'], $row['startDate'], $row['endDate']);
    }
}

?>