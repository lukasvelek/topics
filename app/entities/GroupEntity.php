<?php

namespace App\Entities;

class GroupEntity extends AEntity {
    private int $id;
    private string $title;
    private ?string $description;

    public function __construct(int $id, string $title, string $description) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        self::checkTypes($row, ['groupId' => 'int', 'title' => 'string', 'description' => '?string']);

        return new self($row->groupId, $row->title, $row->description);
    }
}

?>