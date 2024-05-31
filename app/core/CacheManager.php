<?php

namespace App\Core;

use App\Configuration;

class CacheManager {
    private function __construct() {}

    public function loadCachedFiles(string $namespace) {
        
    }

    public static function loadCache(string $key, callable $callback, string $namespace = 'default') {
        
    }

    private static function getTemporaryObject() {
        return new self();
    }
}

?>