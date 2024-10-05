<?php

namespace App\Entities;

class ReportEntity extends AEntity {
    private string $id;
    private string $userId;
    private string $entityId;
    private int $entityType;
    private int $category;
    private string $description;
    private int $status;
    private ?string $statusComment;
    private string $dateCreated;

    public function __construct(string $id, string $userId, string $entityId, int $entityType, int $category, string $description, int $status, ?string $statusComment, string $dateCreated) {
        $this->id = $id;
        $this->userId = $userId;
        $this->category = $category;
        $this->status = $status;
        $this->dateCreated = $dateCreated;
        $this->entityId = $entityId;
        $this->entityType = $entityType;
        $this->description = $description;
        $this->statusComment = $statusComment;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getEntityId() {
        return $this->entityId;
    }

    public function getEntityType() {
        return $this->entityType;
    }

    public function getCategory() {
        return $this->category;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getShortenedDescription(int $length) {
        return substr($this->description, 0, $length);
    }

    public function getStatus() {
        return $this->status;
    }

    public function getStatusComment() {
        return $this->statusComment;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['reportId' => 'string', 'userId' => 'string', 'entityId' => 'string', 'entityType' => 'int', 'category' => 'int', 'description' => 'string', 'status' => 'int',
                                'statusComment' => '?string', 'dateCreated' => 'string']);

        return new self($row->reportId, $row->userId, $row->entityId, $row->entityType, $row->category, $row->description, $row->status, $row->statusComment, $row->dateCreated);
    }
}

?>