<?php

namespace App\UI\HTML;

use App\Exceptions\GeneralException;
use App\UI\HTML\Tags\TagA;
use App\UI\HTML\Tags\TagSpan;

class HTML {
    /**
     * @deprecated
     */
    public static function a() {
        return new TagA();
    }

    private ?string $el;
    private array $styles;
    private ?string $text;
    private array $attributes;

    public function __construct() {
        $this->el = null;
        $this->styles = [];
        $this->text = null;
        $this->attributes = [];
        return $this;
    }

    public function el(string $name) {
        $this->el = $name;
        return $this;
    }

    public function setClass(string $class) {
        $this->attributes['class'] = $class;
        return $this;
    }

    public function setStyle(string $key, mixed $value) {
        $this->styles[] = $key . ': ' . $value;
        return $this;
    }

    public function setText(string $text) {
        $this->text = $text;
        return $this;
    }

    public function setTitle(string $title) {
        $this->attributes['title'] = $title;
        return $this;
    }

    public function setHref(string $href) {
        $this->attributes['href'] = $href;
        return $this;
    }

    public function setOnClick(string $onClick) {
        $this->attributes['onclick'] = $onClick;
        return $this;
    }

    public function toString() {
        if($this->el === null) {
            throw new GeneralException('No element type is set.');
        }

        $code = '<' . $this->el;

        $tmps = [];
        foreach($this->attributes as $key => $value) {
            $tmp = $key;

            if($value !== null) {
                $tmp .= '="' . $value . '"';
                $tmps[] = $tmp;
            } else {
                $tmps = array_merge([$tmp], $tmps);
            }
        }

        $code .= ' ' . implode(' ', $tmps);

        if(!empty($this->styles)) {
            $code .= ' style="' . implode('; ', $this->styles) . '"';
        }

        $code .= '>';

        $code .= $this->text;

        $code .= '</' . $this->el . '>';

        return $code;
    }

    public static function new() {
        return new self();
    }
}

?>