<?php

namespace App\Core;

use App\Configuration;
use Exception;

class CacheManager {
    private function __construct() {}

    public function loadCachedFiles(string $namespace) {
        $filename = $this->generateFilename($namespace);
        
        $path = $this->createPath($namespace) . $filename;
        
        if(!FileManager::fileExists($path)) {
            return null;
        } else {
            return FileManager::loadFile($path);
        }
    }

    public function saveCachedFiles(string $namespace, array|string $content) {
        $filename = $this->generateFilename($namespace);

        $path = $this->createPath($namespace);

        return FileManager::saveFile($path, $filename, $content, true);
    }

    private function createPath(string $namespace) {
        global $app;
        return $app->cfg['APP_REAL_DIR'] . $app->cfg['CACHE_DIR'] . $namespace . '\\';
    }

    private function generateFilename(string $namespace) {
        $filename = date('Y-m-d') . $namespace;
        $filename = md5($filename);

        return $filename;
    }

    public static function loadCache(mixed $key, callable $callback, string $namespace = 'default') {
        $obj = self::getTemporaryObject();
        $file = $obj->loadCachedFiles($namespace);
        $save = false;
        $result = null;

        if($file === null) {
            $result = $callback();
            $file[$key] = $result;
            $save = true;
        } else {
            $file = unserialize($file);

            if(array_key_exists($key, $file)) {
                $result = $file[$key];
            } else {
                $result = $callback();
                $file[$key] = $result;
                $save = true;
            }
        }

        if($save === true) {
            $file = serialize($file);
            
            $obj->saveCachedFiles($namespace, $file);
        }

        return $result;
    }

    public static function saveFlashMessageToCache(array $data) {
        $obj = self::getTemporaryObject();
        $file = $obj->loadCachedFiles('flashMessages');

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[] = $data;

        $file = serialize($file);

        $obj->saveCachedFiles('flashMessages', $file);

        return true;
    }

    public static function loadFlashMessages() {
        $obj = self::getTemporaryObject();
        $file = $obj->loadCachedFiles('flashMessages');
        
        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        return $file;
    }

    public static function invalidateCache(string $namespace) {
        global $app;
        FileManager::deleteFolderRecursively($app->cfg['APP_REAL_DIR'] . $app->cfg['CACHE_DIR'] . $namespace . '\\');
    }

    private static function getTemporaryObject() {
        return new self();
    }
}

?>