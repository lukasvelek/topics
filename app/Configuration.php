<?php

namespace App;

use App\Core\FileManager;
use App\Exceptions\FileDoesNotExistException;

class Configuration {
    public static function getAppName() {
        return self::getCfg('APP_NAME');
    }

    public static function getLogLevel() {
        return self::getCfg('LOG_LEVEL');
    }

    public static function getLogLevelSql() {
        return self::getCfg('SQL_LOG_LEVEL');
    }

    public static function getAppRealDir() {
        return self::getCfg('APP_REAL_DIR');
    }

    public static function getLogDir() {
        return self::getCfg('LOG_DIR');
    }

    public static function getCacheDir() {
        return self::getCfg('CACHE_DIR');
    }

    private static function getCfg(string $param) {
        $path = 'config.local.php';

        if(!FileManager::fileExists($path)) {
            throw new FileDoesNotExistException($path);
        }

        require_once($path);

        if(isset($cfg)) {
            if(array_key_exists($param, $cfg)) {
                return $cfg[$param];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}

?>