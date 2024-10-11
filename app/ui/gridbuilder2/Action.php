<?php

namespace App\UI\GridBuilder2;

use App\Core\DB\DatabaseRow;
use App\UI\HTML\HTML;
use Exception;

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

    public function __construct(string $name) {
        $this->name = $name;

        $this->onCanRender = [];
        $this->onRender = [];

        $this->html = HTML::el('span');

        return $this;
    }

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

    public function setTitle(string $title) {
        $this->title = $title;
    }

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

        return $result;
    }
}

?>