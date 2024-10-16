<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;
use Exception;

/**
 * Class representing cell in a grid table
 * 
 * @author Lukas Velek
 */
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

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();

        $this->onRenderCol = [];
        $this->onExportCol = [];
        $this->isHeader = false;
        $this->span = 1;
        $this->html = HTML::el('td');
    }

    /**
     * Sets if the cell is in header part of the grid
     * 
     * @param bool $header Is in header?
     */
    public function setHeader(bool $header = true) {
        $this->isHeader = $header;
    }

    /**
     * Sets the cell name
     * 
     * @param string $name Cell name
     */
    public function setName(string $name) {
        $this->name = $name;
    }

    /**
     * Returns cell's name
     * 
     * @return string Cell's name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the cell content
     * 
     * @param string|HTML $content Cell content
     */
    public function setContent(string|HTML $content) {
        $this->content = $content;
    }

    /**
     * Sets the cell class
     * 
     * @param string $class Cell class
     */
    public function setClass(string $class) {
        $this->class = $class;
    }

    /**
     * Sets the cell span
     * 
     * @param int $span Cell span
     */
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

    /**
     * Processes all cell render callbacks
     */
    private function processRender() {
        if(!empty($this->onRenderCol)) {
            foreach($this->onRenderCol as $render) {
                try {
                    $this->content = $render($this->content);
                } catch(Exception $e) {}
            }
        }
    }
}

?>