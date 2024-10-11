<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;

class Row extends AElement {
    private string|int|null $primaryKey;
    public HTML $html;
    /**
     * @var array<Cell> $cells
     */
    private array $cells;

    public function __construct() {
        parent::__construct();

        $this->cells = [];
        $this->html = HTML::el('tr');
    }

    public function setPrimaryKey(string|int|null $primaryKey) {
        $this->primaryKey = $primaryKey;
    }

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

    private function processRender() {
        $content = '';

        foreach($this->cells as $cell) {
            $content .= $cell->output()->toString();
        }

        return $content;
    }

    public function export(): string {
        return '';
    }
}

?>