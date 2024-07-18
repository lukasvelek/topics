<?php

namespace App\UI\GridBuilder;

use App\UI\IRenderable;

class Cell implements IRenderable {
    private mixed $text;
    private bool $isHeader;
    private array $attributes;

    public function __construct() {
        $this->text = null;
        $this->isHeader = false;
        $this->attributes = [];
    }

    public function setHeader(bool $isHeader = true) {
        $this->isHeader = $isHeader;
    }

    public function setValue(mixed $value) {
        if($value === NULL) {
            $value = '-';
        }
        $this->text = $value;
    }

    public function setColspan(int $colspan) {
        if($colspan > 1) {
            $this->attributes['colspan'] = $colspan;
        }
    }

    public function setElementId(string $htmlElementId) {
        $this->attributes['id'] = $htmlElementId;
    }

    public function setStyle(string $style) {
        $this->attributes['style'] = $style;
    }

    public function render() {
        $code = '<';
        if($this->isHeader) {
            $code .= 'th';
        } else {
            $code .= 'td';
        }

        if(!empty($this->attributes)) {
            $tmp = [];

            foreach($this->attributes as $k => $v) {
                $tmp[] = $k . '="' . $v . '"';
            }

            $code .= ' ' . implode(' ', $tmp);
        }

        $code .= '>';

        if($this->text === NULL) {
            $this->text = '-';
        }
        $code .= $this->text;

        $code .= '</';
        if($this->isHeader) {
            $code .= 'th';
        } else {
            $code .= 'td';
        }
        $code .= '>';

        return $code;
    }
}

?>