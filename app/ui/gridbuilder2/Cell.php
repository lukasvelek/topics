<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;
use Exception;

class Cell extends AElement {
    private string|HTML $content;
    private string $name;
    private HTML $html;
    private bool $isHeader;
    private string $class;

    public array $onRenderCol;
    public array $onExportCol;

    public function __construct() {
        parent::__construct();

        $this->onRenderCol = [];
        $this->onExportCol = [];
        $this->isHeader = false;
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

    public function output(): HTML {
        $tag = 'td';
        if($this->isHeader) {
            $tag = 'th';
        }

        $this->processRender();

        $this->html = HTML::el($tag);
        $this->html->id('col-' . $this->name);
        $this->html->text($this->content);
        
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