<?php

namespace App\UI\GridBuilder2;

use App\Core\DB\DatabaseRow;
use App\UI\HTML\HTML;
use Exception;

/**
 * Grid action class representation
 * 
 * @author Lukas Velek
 */
class Action implements IHTMLOutput {
    public string $name;
    private string $title;

    /**
     * Methods called with parameters: DatabaseRow $row, Row $_row
     */
    public array $onCanRender;
    /**
     * Methods called with parameters: mixed $primaryKey, DatabaseRow $row, Row $_row, HTML $html
     */
    public array $onRender;

    private DatabaseRow $row;
    private Row $_row;
    private HTML $html;
    private mixed $primaryKey;

    /**
     * Class constructor
     * 
     * @param string $name Action name
     * @return self
     */
    public function __construct(string $name) {
        $this->name = $name;

        $this->onCanRender = [];
        $this->onRender = [];

        $this->html = HTML::el('span');

        return $this;
    }

    /**
     * Destructs the action
     */
    public function __destruct() {
        unset($this->name, $this->onCanRender, $this->onRender, $this->html, $this->name);
    }

    /**
     * Injects mandatory parameters
     * 
     * @param DatabaseRow $row DatabaseRow instance
     * @param Row $_row Row instance
     * @param mixed $primaryKey Primary key
     */
    public function inject(DatabaseRow $row, Row $_row, mixed $primaryKey) {
        $this->row = $row;
        $this->_row = $_row;
        $this->primaryKey = $primaryKey;
    }

    public function output(): HTML {
        $this->html->id('col-actions-' . $this->name);

        if(isset($this->title)) {
            $this->html->title($this->title);
        }

        $this->html->text($this->processText());

        return $this->html;
    }

    /**
     * Sets the title
     * 
     * @param string $title Action title
     */
    public function setTitle(string $title) {
        $this->title = $title;
    }

    /**
     * Processes text displayed
     * 
     * @return string Text
     */
    private function processText() {
        $result = '-';

        if(!empty($this->onRender)) {
            foreach($this->onRender as $render) {
                try {
                    $result = $render($this->primaryKey, $this->row, $this->_row, $this->html);
                } catch(Exception $e) {
                    $result = '-';
                }
            }
        } else {
            $result = '-';
        }

        if($result === null) {
            $result = '-';
        }

        return $result;
    }
}

?>