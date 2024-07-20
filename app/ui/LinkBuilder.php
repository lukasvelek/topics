<?php

namespace App\UI;

class LinkBuilder implements IRenderable {
    private array $elements;
    private string $text;
    private array $urlParts;

    public function __construct() {
        $this->elements = [];
        $this->text = '';
        $this->urlParts = [];
    }

    public function render() {
        if(!empty($this->urlParts)) {
            $this->processUrl();
        }

        $code = '<a ' . $this->processElements() . '>' . $this->text . '</a>';

        return $code;
    }

    public function setClass(string $class) {
        $this->elements['class'] = $class;

        return $this;
    }

    public function setHref(string $href) {
        $this->elements['href'] = $href;
    }

    public function setUrl(array $url) {
        $this->urlParts = array_merge($this->urlParts, $url);

        return $this;
    }

    public function setText(string $text) {
        $this->text = $text;

        return $this;
    }

    public function setStyle(string $style) {
        $this->elements['style'] = $style;

        return $this;
    }

    public function setOnclick(string $onclickMethod) {
        $this->elements['onclick'] = $onclickMethod;

        return $this;
    }

    private function processUrl() {
        $tmp = [];

        foreach($this->urlParts as $k => $v) {
            $tmp[] = $k . '=' . $v;
        }

        $this->elements['href'] = '?' . implode('&', $tmp);
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
        $obj = self::createSimpleLinkObject($text, $url, $class);

        return $obj->render();
    }

    public static function createSimpleLinkObject(string $text, array $url, string $class) {
        $lb = new self();

        $lb ->setText($text)
            ->setUrl($url)
            ->setClass($class);

        return $lb;
    }

    public static function convertUrlArrayToString(array $url) {
        $tmp = [];
        foreach($url as $k => $v) {
            $tmp[] = $k . '=' . $v;
        }

        return '?' . implode('&', $tmp);
    }

    public static function createJSOnclickLink(string $text, string $jsMethod, string $class) {
        $lb = new self();

        $lb ->setText($text)
            ->setOnclick($jsMethod)
            ->setClass($class)
            ->setHref('#');

        return $lb->render();
    }
}

?>