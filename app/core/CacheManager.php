<?php

namespace App\Core;

use App\Core\Datetypes\DateTime;
use App\Logger\Logger;

class CacheManager {
    private ?Logger $logger;

    public function __construct(?Logger $logger) {
        $this->logger = $logger;
    }

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

    public function loadCache(mixed $key, callable $callback, string $namespace = 'default', ?string $method = null) {
        $file = $this->loadCachedFiles($namespace);
        $save = false;
        $result = null;

        $cacheHit = true;

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
            $cacheHit = false;
            
            $this->saveCachedFiles($namespace, $file);
        }

        $this->logger->logCache($method ?? __METHOD__, $cacheHit);

        return $result;
    }

    public function deleteFlashMessages() {
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        $this->invalidateCache('flashMessages-' . $userId);
    }

    public function saveFlashMessageToCache(array $data) {
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        $file = $this->loadCachedFiles('flashMessages-' . $userId);

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[] = $data;

        $file = serialize($file);

        $result = $this->saveCachedFiles('flashMessages-' . $userId, $file);

        return $result > 0;
    }

    public function loadFlashMessages() {
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        $file = $this->loadCachedFiles('flashMessages-' . $userId);
        
        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        return $file;
    }

    public function invalidateCache(string $namespace) {
        global $app;
        FileManager::deleteFolderRecursively($app->cfg['APP_REAL_DIR'] . $app->cfg['CACHE_DIR'] . $namespace . '\\');
    }

    public function invalidateCacheBulk(array $namespaces) {
        foreach($namespaces as $namespace) {
            self::invalidateCache($namespace);
        }
    }

    public function savePageToCache(string $moduleName, string $presenterName, string $content) {
        $file = $this->loadCachedFiles('cachedPages');

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[$moduleName . '_' . $presenterName] = $content;

        $file = serialize($file);

        $result = $this->saveCachedFiles('cachedPages', $file);

        return $result > 0;
    }
    
    public function loadPagesFromCache() {
        $file = $this->loadCachedFiles('cachedPages');

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        return $file;
    }
}

?>