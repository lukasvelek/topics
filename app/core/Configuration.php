<?php

namespace App\Core;

use Exception;

class Configuration {
    public static function getAppName() {
        return self::getCfg('APP_NAME');
    }

    private static function getCfg(string $param) {
        $path = getcwd() . '\\config.local.php';

        if(!file_exists($path)) {
            throw new Exception('Configuration file does not exist!');
        }

        require_once($path);

        if(array_key_exists($param, $cfg)) {
            return $cfg[$param];
        } else {
            return null;
        }
    }
}

?>