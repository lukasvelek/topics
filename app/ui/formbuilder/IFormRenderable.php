<?php

namespace App\UI\FormBuilder;

use App\UI\IRenderable;

interface IFormRenderable extends IRenderable {
    function getName();
    function getTagName();
}

?>