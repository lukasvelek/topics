<?php

namespace App\Entities;

use App\UI\IRenderable;

class TopicTagEntity implements IRenderable {
    private string $text;
    private string $color;
    private string $bgColor;
    private array $parameters;
    private string $tag;

    public function __construct(string $text, string $color, string $bgColor) {
        $this->text = $text;
        $this->color = $color;
        $this->bgColor = $bgColor;

        $this->tag = 'div';
        $this->parameters = [];
    }

    public function setTag(string $tag) {
        $this->tag = $tag;
    }

    private function init() {
        $this->parameters[] = 'border: 1px solid ' . $this->color;
        $this->parameters[] = 'border-radius: 10px';
        $this->parameters[] = 'padding: 5px';
        $this->parameters[] = 'margin: 5px';
        
        $this->parameters[] = 'text-align: center';

        $this->parameters[] = 'color: ' . $this->color;
        $this->parameters[] = 'background-color: ' . $this->bgColor;
    }
    
    public function render() {
        $this->init();
        
        $code = '<' . $this->tag . ' class="col-md" style="' . implode('; ', $this->parameters) . '">';

        $code .= $this->text;

        $code .= '</' . $this->tag . '>';

        return $code;
    }
}

?>