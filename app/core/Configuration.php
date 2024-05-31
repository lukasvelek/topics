<?php

namespace App\Core;

use Exception;

class Configuration {
    public static function getAppName() {
        return self::getCfg('APP_NAME');
    }

    private static function getCfg(string $param) {
        $path = getcwd() . '\\config.local.php';

        try {
            FileManager::loadFile($path);
        } catch(Exception $e) {
            throw $e;
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