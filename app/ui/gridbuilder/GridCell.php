<?php

namespace App\UI\GridBuilder;

use App\UI\IRenderable;

class Cell implements IRenderable {
    private mixed $text;
    private bool $isHeader;
    private array $attributes;
    private bool $isForAction;

    public function __construct() {
        $this->text = null;
        $this->isHeader = false;
        $this->attributes = [];
        $this->isForAction = false;
    }

    public function setHeader(bool $isHeader = true) {
        $this->isHeader = $isHeader;
    }

    public function setIsForAction(bool $isForAction = true) {
        $this->isForAction = $isForAction;
    }

    public function setValue(mixed $value) {
        if($value === NULL) {
            $value = '-';
        }
        if($value instanceof IRenderable) {
            $value = $value->render();
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

    public function addStyle(string $style) {
        if(!isset($this->attributes['style'])) {
            $this->setStyle($style);
            return;
        }

        $this->attributes['style'] .= '; ' . $style;
    }

    public function setTextColor(string $color) {
        $this->addStyle('color: ' . $color);
    }

    public function setTitle(string $title) {
        $this->attributes['title'] = $title;
    }

    public function render() {
        $code = '<';
        if($this->isHeader) {
            $code .= 'th';
        } else {
            $code .= 'td';
        }

        if($this->isForAction) {
            if(array_key_exists('class', $this->attributes)) {
                $this->attributes['class'] .= ' grid-cell-action';
            } else {
                $this->attributes['class'] = 'grid-cell-action';
            }
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