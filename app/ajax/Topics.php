<?php

require_once('Ajax.php');

function search() {
    $query = httpGet('query');

    return $query;
}

?>