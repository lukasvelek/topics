<?php

namespace App\UI\HTML\Tags;

use App\HTML\Tags\ACommonTag;

class TagSpan extends ACommonTag {
    public function __construct() {
        parent::__construct('span');

        return $this;
    }

    public function setText(string $text) {
        $this->setContent($text);

        return $this;
    }

    public function setColor(string $color) {
        $this->style(['color' => $color]);

        return $this;
    }
}

?>