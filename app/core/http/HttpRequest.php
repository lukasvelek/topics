<?php

namespace App\Core\Http;

class HttpRequest {
    public array $query;
    public bool $isAjax;

    public function __construct() {
        $this->query = [];
        $this->isAjax = false;
    }
}

?>