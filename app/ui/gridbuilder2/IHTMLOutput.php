<?php

namespace App\UI\GridBuilder2;

use App\UI\HTML\HTML;

/**
 * Common interface that makes sure it's implementations can output HTML code
 * 
 * @author Lukas Velek
 */
interface IHTMLOutput {
    /**
     * Outputs HTML code
     * 
     * @return HTML
     */
    function output(): HTML;
}

?>