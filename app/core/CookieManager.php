<?php

namespace App\Core;

class CookieManager {
    public static function getCookie(string $key) {
        if(isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        } else {
            return null;
        }
    }

    public static function setCookie(string $key, mixed $value) {
        $_COOKIE[$key] = $value;
    }
}

?>