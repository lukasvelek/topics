<?php

namespace App\UI\GridBuilder2;

abstract class AElement implements IHTMLOutput, IExport {
    protected array $attributes;

    protected function __construct() {
        $this->attributes = [];
    }
}

?>