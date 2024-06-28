<?php

namespace App\HTML\Tags;

use App\UI\IRenderable;

abstract class ACommonTag implements IRenderable {
    private string $tag;
    private bool $doubleSided;
    private array $customElements;
    private ?string $betweenBracketsContent;

    protected function __construct(string $tag, bool $doubleSided = true) {
        $this->tag = $tag;
        $this->doubleSided = $doubleSided;
        $this->customElements = [];
        $this->betweenBracketsContent = null;
    }

    public function onClick(string $code) {
        $this->setCustomElement('onclick', $code);
        return $this;
    }

    public function style(array $parts) {
        $tmp = [];
        foreach($parts as $k => $v) {
            $tmp[] = $k . ': ' . $v;
        }
        
        $code = implode('; ', $tmp);

        $this->setCustomElement('style', $code);
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
        $code = '<' . $this->tag;

        $tmp = [];
        foreach($this->customElements as $k => $v) {
            if($v !== null) {
                $tmp[] = $k . '="' . $v . '"';
            } else {
                $tmp[] = $k;
            }
        }

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
}

?>