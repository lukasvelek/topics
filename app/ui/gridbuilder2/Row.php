<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;

class Row extends AElement {
    private string|int $primaryKey;
    private HTML $html;
    /**
     * @var array<Cell> $cells
     */
    private array $cells;

    public array $onRenderRow;

    public function __construct() {
        parent::__construct();

        $this->cells = [];
        $this->onRenderRow = [];
    }

    public function setPrimaryKey(string|int $primaryKey) {
        $this->primaryKey = $primaryKey;
    }

    public function addCell(Cell $cell) {
        $this->cells[] = $cell;
    }

    public function output(): HTML {
        $this->html = HTML::el('tr');
        $this->html->id('row-' . $this->primaryKey);
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