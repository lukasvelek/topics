<?php

namespace App\UI\GridBuilder2;

/**
 * Class that represents a column in grid table
 * 
 * @author Lukas Velek
 */
class Column {
    /**
     * Methods are called with parameters: DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value
     */
    public array $onRenderColumn;
    /**
     * Methods are called with parameters: DatabaseRow $row, mixed $value
     */
    public array $onExportColumn;

    private string $name;

    /**
     * Class constructor
     * 
     * @param string $name Column name
     */
    public function __construct(string $name) {
        $this->name = $name;
    }

    /**
     * Returns the column name
     */
    public function getName() {
        return $this->name;
    }
}

?>