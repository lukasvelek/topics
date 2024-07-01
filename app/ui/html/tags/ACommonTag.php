<?php

namespace App\HTML\Tags;

use App\UI\IRenderable;

abstract class ACommonTag implements IRenderable {
    private string $tag;
    private bool $doubleSided;
    private array $customElements;
    private ?string $betweenBracketsContent;
    private array $styleArray;

    protected function __construct(string $tag, bool $doubleSided = true) {
        $this->tag = $tag;
        $this->doubleSided = $doubleSided;
        $this->customElements = [];
        $this->betweenBracketsContent = null;
        $this->styleArray = [];
    }

    public function onClick(string $code) {
        $this->setCustomElement('onclick', $code);
        return $this;
    }

    public function style(array $parts) {
        $this->styleArray = array_merge($this->styleArray, $parts);
        return $this;
    }

    public function class(string $class) {
        $this->customElements['class'] = $class;
        return $this;
    }

    public function id(string $id) {
        $this->customElements['id'] = $id;
        return $this;
    }

    public function setContent(string $content) {
        $this->betweenBracketsContent = $content;
    }

    protected function setCustomElement(string $key, mixed $value) {
        $this->customElements[$key] = $value;
    }

    public function render() {
        $styleAttr = $this->convertStyles();

        $code = '<' . $this->tag;

        $tmp = [];
        foreach($this->customElements as $k => $v) {
            if($v !== null) {
                $tmp[] = $k . '="' . $v . '"';
            } else {
                $tmp[] = $k;
            }
        }

        $tmp[] = 'style="' . $styleAttr . '"';

        $code .= ' ' . implode(' ', $tmp) . '>';
        if($this->betweenBracketsContent !== null) {
            $code .= $this->betweenBracketsContent;
        }
        if($this->doubleSided) {
            $code .= '</' . $this->tag . '>';
        }

        return $code;
    }

    public function get() {
        return $this->render();
    }

    private function convertStyles() {
        $tmp = [];

        foreach($this->styleArray as $k => $v) {
            $tmp[] = $k . ': ' . $v;
        }

        return implode('; ', $tmp);
    }
}

?>