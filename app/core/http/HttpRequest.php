<?php

namespace App\Core\Http;

/**
 * HttpRequest represents a single HTTP request. It contains query parameters and a boolean that indicates whether it is a AJAX call or not.
 * 
 * @author Lukas Velek
 */
class HttpRequest {
    /**
     * Query parameters
     * 
     * @var array<string, mixed> $query
     */
    public array $query;

    /**
     * Is the call AJAX?
     * 
     * @var bool $isAjax
     */
    public bool $isAjax;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->query = [];
        $this->isAjax = false;
    }
}

?>