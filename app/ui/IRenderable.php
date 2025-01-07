<?php

namespace App\UI;

/**
 * Interface used in all UI elements. It's implementations can be rendered.
 * 
 * @author Lukas Velek
 */
interface IRenderable {
    /**
     * Renders element's content
     */
    function render();
}

?>