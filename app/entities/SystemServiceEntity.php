<?php

namespace App\Entities;

class SystemServiceEntity extends AEntity {
    private string $id;
    private string $title;
    private string $scriptPath;
    private ?string $dateStarted;
    private ?string $dateEnded;
    private int $status;

    public function __construct(string $id, string $title, string $scriptPath, ?string $dateStarted, ?string $dateEnded, int $status) {
        $this->id = $id;
        $this->title = $title;
        $this->scriptPath = $scriptPath;
        $this->dateStarted = $dateStarted;
        $this->dateEnded = $dateEnded;
        $this->status = $status;
    }

    public function getId() {
        return $this->id;
    }
    
    public function getTitle() {
        return $this->title;
    }

    public function getScriptPath() {
        return $this->scriptPath;
    }

    public function getDateStarted() {
        return $this->dateStarted;
    }

    public function getDateEnded() {
        return $this->dateEnded;
    }

    public function getStatus() {
        return $this->status;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['serviceId' => 'string', 'title' => 'string', 'scriptPath' => 'string', 'dateStarted' => '?string', 'dateEnded' => '?string', 'status' => 'int']);

        return new self($row->serviceId, $row->title, $row->scriptPath, $row->dateStarted, $row->dateEnded, $row->status);
    }
}

?>