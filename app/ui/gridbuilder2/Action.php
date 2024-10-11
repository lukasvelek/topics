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
     * Methods called with parameters: DatabaseRow $row, Row $_row
     */
    public array $onRender;

    private DatabaseRow $row;
    private Row $_row;

    public function __construct(string $name) {
        $this->name = $name;

        $this->onCanRender = [];
        $this->onRender = [];

        return $this;
    }

    public function inject(DatabaseRow $row, Row $_row) {
        $this->row = $row;
        $this->_row = $_row;
    }

    public function output(): HTML {
        $el = HTML::el('span');
        $el->id('col-actions-' . $this->name);

        if(isset($this->title)) {
            $el->title($this->title);
        }

        $el->text($this->processText());

        return $el;
    }

    public function setTitle(string $title) {
        $this->title = $title;
    }

    private function processText() {
        if(!empty($this->onRender)) {
            foreach($this->onRender as $render) {
                try {
                    return $render($this->row, $this->_row);
                } catch(Exception $e) {
                    return '-';
                }
            }
        } else {
            return '-';
        }

        return '-';
    }
}

?>