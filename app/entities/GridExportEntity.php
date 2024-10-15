<?php

namespace App\Entities;

class GridExportEntity extends AEntity {
    private string $id;
    private string $userId;
    private string $hash;
    private ?string $filename;
    private string $gridName;
    private ?int $entryCount;
    private string $dateCreated;
    private ?string $dateFinished;

    public function __construct(string $id, string $userId, string $hash, ?string $filename, string $gridName, ?int $entryCount, string $dateCreated, ?string $dateFinished) {
        $this->id = $id;
        $this->userId = $userId;
        $this->hash = $hash;
        $this->filename = $filename;
        $this->gridName = $gridName;
        $this->entryCount = $entryCount;
        $this->dateCreated = $dateCreated;
        $this->dateFinished = $dateFinished;
    }

    public function getId() {
        return $this->id;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getHash() {
        return $this->hash;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function getGridName() {
        return $this->gridName;
    }

    public function getEntryCount() {
        return $this->entryCount;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getDateFinished() {
        return $this->dateFinished;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['exportId' => 'string', 'userId' => 'string', 'hash' => 'string', 'filename' => '?string', 'gridName' => 'string', 'entryCount' => '?int', 'dateCreated' => 'string', 'dateFinished' => '?string']);

        return new self($row->exportId, $row->userId, $row->hash, $row->filename, $row->gridName, $row->entryCount, $row->dateCreated, $row->dateFinished);
    }
}

?>