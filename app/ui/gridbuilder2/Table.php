<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;

class Table extends AElement {
    /**
     * @var array<Row> $rows
     */
    private array $rows;

    public function __construct(array $rows) {
        parent::__construct();

        $this->rows = $rows;
    }

    public function output(): HTML {
        $table = HTML::el('table');
        $table->text($this->processRender());
        $table->addAtribute('border', '1');

        return $table;
    }

    private function processRender() {
        $content = '';

        foreach($this->rows as $row) {
            $content .= $row->output()->toString();
        }

        return $content;
    }

    public function export(): string {
        return '';
    }
}

?>