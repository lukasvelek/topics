<?php

namespace App\UI\GridBuilder;

use App\UI\IRenderable;

/**
 * @deprecated
 */
class Row implements IRenderable {
    private array $cells;
    private array $attributes;
    private ?string $primaryKey;
    private bool $isHeader;

    public function __construct() {
        $this->cells = [];
        $this->attributes = [];
        $this->primaryKey = null;
        $this->isHeader = false;
    }

    public function addCell(Cell $cell) {
        $this->cells[] = $cell;
    }

    public function setStyle(string $style) {
        $this->attributes['style'] = $style;
    }

    public function setBackgroundColor(string $color) {
        if(array_key_exists('style', $this->attributes)) {
            $this->attributes['style'] .= '; background-color: ' . $color;
        } else {
            $this->attributes['style'] = 'background-color: ' . $color;
        }
    }
    
    public function setDescription(string $description) {
        $this->attributes['title'] = $description;
    }

    public function setPrimaryKey(string $primaryKey) {
        $this->primaryKey = $primaryKey;
    }

    public function hasPrimaryKey() {
        return $this->primaryKey !== null;
    }

    public function setHeader(bool $isHeader = true) {
        $this->isHeader = $isHeader;
    }

    public function render() {
        $code = '<tr';

        if($this->isHeader) {
            if(array_key_exists('style', $this->attributes)) {
                $this->attributes['style'] .= '; position: sticky';
            } else {
                $this->attributes['style'] = 'position: sticky';
            }
        }

        if(!empty($this->attributes)) {
            $tmp = [];

            foreach($this->attributes as $k => $v) {
                $tmp[] = $k . '="' . $v . '"';
            }

            $code .= ' ' . implode(' ', $tmp);
        }

        if($this->hasPrimaryKey()) {
            $code .= ' class="grid-row-' . $this->primaryKey . '"';
        }

        $code .= '>';

        if(!empty($this->cells)) {
            foreach($this->cells as $cell) {
                $code .= $cell->render();
            }
        }

        $code .= '</tr>';

        return $code;
    }
}

?>