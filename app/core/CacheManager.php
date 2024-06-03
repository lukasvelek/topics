<?php

namespace App\Core;

use App\Configuration;
use Exception;

class CacheManager {
    private function __construct() {}

    public function loadCachedFiles(string $namespace) {
        $filename = $this->generateFilename($namespace);
        
        $path = $this->createFilepath($namespace, $filename);
        
        if(!FileManager::fileExists($path)) {
            return null;
        } else {
            return FileManager::loadFile($path);
        }
    }

    public function saveCachedFiles(string $namespace, string $content) {
        $filename = $this->generateFilename($namespace);

        $path = $this->createFilepath($namespace, $filename);

        return FileManager::saveFile($path, $content, true);
    }

    private function createFilepath(string $namespace, string $filename) {
        return Configuration::getAppRealDir() . Configuration::getCacheDir() . $namespace . '/' . $filename . '.tmp';
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
            $result[$key] = $callback();
            $save = true;
        } else {
            $file = unserialize($file);

            if(array_key_exists($key, $file)) {
                $result = $file[$key];
            } else {
                $result[$key] = $callback();
                $file[$key] = $result[$key];
                $save = true;
            }
        }

        if($save === true) {
            $obj->saveCachedFiles($namespace, $file);
        }

        return $result;
    }

    public static function saveFlashMessageToCache(mixed $key, string $text) {
        $obj = self::getTemporaryObject();
        $file = $obj->loadCachedFiles('flashMessages');

        $file = unserialize($file);

        $file[$key] = $text;

        $obj->saveCachedFiles('flashMessages', $file);

        return true;
    }

    public static function loadFlashMessages() {
        $obj = self::getTemporaryObject();
        $file = $obj->loadCachedFiles('flashMessages');
        $file = unserialize($file);

        return $file;
    }

    public static function invalidateCache(string $namespace) {
        FileManager::deleteFolderRecursively()
    }

    private static function getTemporaryObject() {
        return new self();
    }
}

?>