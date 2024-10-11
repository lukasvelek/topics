<?php

namespace App\UI\GridBuilder2;

class Column {
    /**
     * Methods are called with parameters: DatabaseRow $row, Row $_row, Cell $cell, mixed $value
     */
    public array $onRenderColumn;
    public array $onExportColumn;

    private string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
}

?>