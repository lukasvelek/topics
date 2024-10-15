<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;

/**
 * Class that represents a row in grid table
 * 
 * @author Lukas Velek
 */
class Row extends AElement {
    private string|int|null $primaryKey;
    public HTML $html;
    /**
     * @var array<Cell> $cells
     */
    private array $cells;

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();

        $this->cells = [];
        $this->html = HTML::el('tr');
    }

    /**
     * Sets the primary key
     * 
     * @param string|int|null $primaryKey Primary key
     */
    public function setPrimaryKey(string|int|null $primaryKey) {
        $this->primaryKey = $primaryKey;
    }

    /**
     * Adds cell
     * 
     * @param Cell $cell Cell instance
     * @param bool $prepend Should the cell be prepended?
     */
    public function addCell(Cell $cell, bool $prepend = false) {
        if($prepend) {
            $this->cells = array_merge([$cell], $this->cells);
        } else {
            $this->cells[] = $cell;
        }
    }

    public function output(): HTML {
        $this->html->id('row-' . (isset($this->primaryKey) ? $this->primaryKey : ''));
        $this->html->text($this->processRender());

        return $this->html;
    }

    /**
     * Renders all cells in the row
     * 
     * @return string HTML code
     */
    private function processRender() {
        $content = '';

        foreach($this->cells as $cell) {
            $cCell = clone $cell;
            
            $content .= $cCell->output()->toString();
        }

        return $content;
    }
}

?>