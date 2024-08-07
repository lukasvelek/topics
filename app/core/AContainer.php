<?php

abstract class AContainer {
    protected array $functions;
    protected array $objects;

    public function __construct() {
        $this->functions = [];
        $this->objects = [];
    }

    protected function getByType(string $name) {
        
    }
}

?>