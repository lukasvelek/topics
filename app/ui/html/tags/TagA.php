<?php

namespace App\UI\HTML\Tags;

use App\HTML\Tags\ACommonTag;

class TagA extends ACommonTag {
    public function __construct() {
        parent::__construct('a');
        return $this;
    }

    public function text(string $text) {
        $this->setContent($text);
        return $this;
    }

    public function href(string $href) {
        $this->setCustomElement('href', $href);
        return $this;
    }

    public function hrefArray(array $array) {
        return $this->href($this->createHrefFromArray($array));
    }

    private function createHrefFromArray(array $array) {
        $url = '?';

        $tmp = [];
        foreach($array as $k => $v) {
            $tmp[] = $k . '=' . $v;
        }

        $url .= implode('&', $tmp);

        return $url;
    }
}

?>