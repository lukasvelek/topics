<?php

namespace App\UI;

class LinkBuilder implements IRenderable {
    private array $elements;
    private string $text;

    public function __construct() {
        $this->elements = [];
        $this->text = '';
    }

    public function render() {
        $code = '<a ' . $this->processElements() . '>' . $this->text . '</a>';

        return $code;
    }

    public function setClass(string $class) {
        $this->elements['class'] = $class;

        return $this;
    }

    public function setUrl(array $url) {
        $tmp = [];
        foreach($url as $k => $v) {
            $tmp[] = $k . '=' . $v;
        }

        $this->elements['href'] = '?' . implode('&', $tmp);

        return $this;
    }

    public function setText(string $text) {
        $this->text = $text;

        return $this;
    }

    public function setStyle(string $style) {
        $this->elements['style'] = $style;
    }

    private function processElements() {
        $tmp = [];
        $tmpSingles = [];

        foreach($this->elements as $k => $v) {
            if($v === null) {
                $tmpSingles[] = $k;
            } else {
                $tmp[] = $k . '="' . $v . '"';
            }
        }

        $tmp = array_merge($tmp, $tmpSingles);

        return implode(' ', $tmp);
    }

    public static function createSimpleLink(string $text, array $url, string $class) {
        $lb = new self();

        $lb ->setText($text)
            ->setUrl($url)
            ->setClass($class);

        return $lb->render();
    }
}

?>