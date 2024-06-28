<?php

namespace App\Entities;

class SystemStatusEntity implements ICreatableFromRow {
    private int $id;
    private string $name;
    private int $status;
    private ?string $description;
    private string $dateUpdated;

    public function __construct(int $id, string $name, int $status, ?string $description, string $dateUpdated) {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
        $this->description = $description;
        $this->dateUpdated = $dateUpdated;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDateUpdated() {
        return $this->dateUpdated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        return new self($row['systemId'], $row['name'], $row['status'], $row['description'], $row['dateUpdated']);
    }
}

?>