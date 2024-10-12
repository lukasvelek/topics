<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;
use Exception;

class Cell extends AElement {
    private string|HTML $content;
    private string $name;
    public HTML $html;
    private bool $isHeader;
    private string $class;
    private int $span;

    /**
     * Methods are called with parameters: DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value
     */
    public array $onRenderCol;
    public array $onExportCol;

    public function __construct() {
        parent::__construct();

        $this->onRenderCol = [];
        $this->onExportCol = [];
        $this->isHeader = false;
        $this->span = 1;
        $this->html = HTML::el('td');
    }

    public function setHeader(bool $header = true) {
        $this->isHeader = $header;
    }

    public function setName(string $name) {
        $this->name = $name;
    }

    public function setContent(string|HTML $content) {
        $this->content = $content;
    }

    public function setClass(string $class) {
        $this->class = $class;
    }

    public function setSpan(int $span) {
        if($span == 0) {
            return;
        }
        $this->span = $span;
    }

    public function output(): HTML {
        if($this->isHeader) {
            $this->html->changeTag('th');
        }

        $this->processRender();

        $this->html->id('col-' . $this->name);
        $this->html->text($this->content);
        
        if($this->span > 1) {
            $this->html->addAtribute('colspan', $this->span);
        }
        
        if(isset($this->class)) {
            $this->html->class($this->class);
        }

        return $this->html;
    }

    private function processRender() {
        if(!empty($this->onRenderCol)) {
            foreach($this->onRenderCol as $render) {
                try {
                    $this->content = $render($this->content);
                } catch(Exception $e) {}
            }
        }
    }

    public function export(): string {
        return $this->processExport();
    }

    private function processExport() {
        if(!empty($this->onExportCol)) {
            foreach($this->onExportCol as $export) {
                try {
                    return $export($this->content);
                } catch(Exception $e) {
                    return $this->content;
                }
            }
        }

        return $this->content;
    }
}

?>