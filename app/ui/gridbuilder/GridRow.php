<?php

namespace App\UI\GridBuilder;

use App\UI\IRenderable;

class Row implements IRenderable {
    private array $cells;
    private array $attributes;

    public function __construct() {
        $this->cells = [];
        $this->attributes = [];
    }

    public function addCell(Cell $cell) {
        $this->cells[] = $cell;
    }

    public function setStyle(string $style) {
        $this->attributes['style'] = $style;
    }

    public function render() {
        $code = '<tr';

        if(!empty($this->attributes)) {
            $tmp = [];

            foreach($this->attributes as $k => $v) {
                $tmp[] = $k . '="' . $v . '"';
            }

            $code .= ' ' . implode(' ', $tmp);
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