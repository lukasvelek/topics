<?php

namespace App\UI\GridBuilder2;

/**
 * Common class for grid elements
 * 
 * @author Lukas Velek
 */
abstract class AElement implements IHTMLOutput {
    protected array $attributes;

    /**
     * Class constructor
     */
    protected function __construct() {
        $this->attributes = [];
    }
}

?>