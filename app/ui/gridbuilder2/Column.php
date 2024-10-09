<?php

namespace App\UI\GridBuilder2;

class Column {
    public array $onRenderColumn;
    public array $onExportColumn;

    private string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
}

?>