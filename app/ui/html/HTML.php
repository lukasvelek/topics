<?php

namespace App\UI\HTML;

use App\UI\HTML\Tags\TagA;
use App\UI\HTML\Tags\TagSpan;

class HTML {
    public static function a() {
        return new TagA();
    }

    public static function span() {
        return new TagSpan();
    }
}

?>