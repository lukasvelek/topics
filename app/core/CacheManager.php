<?php

namespace App\Core;

use App\Configuration;
use App\Core\Datetypes\DateTime;
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
        $date = new DateTime();
        $date->format('Y-m-d');
        
        $filename = $date . $namespace;
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

    public static function deleteFlashMessages() {
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        self::invalidateCache('flashMessages-' . $userId);
    }

    public static function saveFlashMessageToCache(array $data) {
        $obj = self::getTemporaryObject();
        
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        $file = $obj->loadCachedFiles('flashMessages-' . $userId);

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[] = $data;

        $file = serialize($file);

        $result = $obj->saveCachedFiles('flashMessages-' . $userId, $file);

        return $result > 0;
    }

    public static function loadFlashMessages() {
        $obj = self::getTemporaryObject();
        
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        $file = $obj->loadCachedFiles('flashMessages-' . $userId);
        
        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        return $file;
    }

    public static function invalidateCache(string $namespace) {
        global $app;
        FileManager::deleteFolderRecursively($app->cfg['APP_REAL_DIR'] . $app->cfg['CACHE_DIR'] . $namespace . '\\');
    }

    public static function invalidateCacheBulk(array $namespaces) {
        foreach($namespaces as $namespace) {
            self::invalidateCache($namespace);
        }
    }

    private static function getTemporaryObject() {
        return new self();
    }

    public static function savePageToCache(string $moduleName, string $presenterName, string $content) {
        $obj = self::getTemporaryObject();

        $file = $obj->loadCachedFiles('cachedPages');

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[$moduleName . '_' . $presenterName] = $content;

        $file = serialize($file);

        $result = $obj->saveCachedFiles('cachedPages', $file);

        return $result > 0;
    }
    
    public static function loadPagesFromCache() {
        $obj = self::getTemporaryObject();

        $file = $obj->loadCachedFiles('cachedPages');

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        return $file;
    }
}

?>