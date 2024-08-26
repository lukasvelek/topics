<?php

namespace App\Core;

use App\Core\Datetypes\DateTime;
use App\Logger\Logger;

/**
 * CacheManager allows caching. It contains methods needed to operate with cache.
 * 
 * @author Lukas Velek
 */
class CacheManager {
    private Logger $logger;

    /**
     * Class constructor
     * 
     * @param Logger $logger Logger instance
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Loads cached files for a given namespace
     * 
     * @param string $namespace Namespace name
     * @param bool $flashMessage Is the method called because of flash messages?
     * @return null|string File contents or null
     */
    public function loadCachedFiles(string $namespace, bool $flashMessage = false) {
        if(!$this->logger->getCfg()['ENABLE_CACHING'] && !$flashMessage) {
            return null;
        }

        $filename = $this->generateFilename($namespace);
        
        $path = $this->createPath($namespace) . $filename;
        
        if(!FileManager::fileExists($path)) {
            return null;
        } else {
            $file = FileManager::loadFile($path);

            if($file === false) {
                return null;
            }

            return $file;
        }
    }

    /**
     * Saves cache to file in a given namespaces
     * 
     * @param string $namespace Namespace name
     * @param array|string $content Content to be saved to the file
     * @param bool $flashMessage Is the method called because of flash messages?
     * @return bool True if successful or false if not
     */
    public function saveCachedFiles(string $namespace, array|string $content, bool $flashMessage = false) {
        if(!$this->logger->getCfg()['ENABLE_CACHING'] && !$flashMessage) {
            return false;
        }

        $filename = $this->generateFilename($namespace);

        $path = $this->createPath($namespace);

        $result = FileManager::saveFile($path, $filename, $content, true);

        if($result !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the path of a given namespace
     * 
     * @param string $namespace Namespace name
     * @return string Namespace path
     */
    private function createPath(string $namespace) {
        global $cfg;
        
        return $cfg['APP_REAL_DIR'] . $cfg['CACHE_DIR'] . $namespace . '\\';
    }

    /**
     * Generates filename for a file in a given namespace
     * 
     * @param string $namespace Namespace name
     * @return string Filename
     */
    private function generateFilename(string $namespace) {
        $date = new DateTime();
        $date->format('Y-m-d');
        
        $filename = $date . $namespace;
        $filename = md5($filename);

        return $filename;
    }

    /**
     * Loads data from cache and then tries to find the given key. If the key is not found the callback is called and the result returned is cached with the given key. Finally the result is returned.
     * 
     * @param mixed $key Cache key
     * @param callback $callback Callback called if key is not found
     * @param string $namespace Namespace name
     * @param string $method Calling method's name
     * @return mixed Result
     */
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

    /**
     * Saves data to cache. The value is obtained by calling the callback and the result is saved to the cache of the namespace.
     * 
     * @param mixed $key Cache key
     * @param callback $callback Callback called to get the result
     * @param string $namespace Namespace name
     * @param string $method Calling method's name
     * @return bool True if successful or false if not
     */
    public function saveCache(mixed $key, callable $callback, string $namespace = 'default', ?string $method = null) {
        $file = $this->loadCachedFiles($namespace);
        
        if($file === null) {
            $file = [];
        } else {
            $file = unserialize($file);
        }
        
        $result = $callback();
        $file[$key] = $result;

        $file = serialize($file);

        $saveResult = $this->saveCachedFiles($namespace, $file);

        $this->logger->logCacheSave($method ?? __METHOD__, $key, $namespace);

        if($saveResult !== null && $saveResult !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes a cached flash message
     * 
     * @return bool True if successful or false if not
     */
    public function deleteFlashMessages() {
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        $this->invalidateCache('flashMessages-' . $userId, true);
    }

    /**
     * Saves a flash message to cache
     * 
     * @param array $data Flash message data
     * @return bool True if successful or false if not
     */
    public function saveFlashMessageToCache(array $data) {
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        $file = $this->loadCachedFiles('flashMessages-' . $userId, true);

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[] = $data;

        $file = serialize($file);

        return $this->saveCachedFiles('flashMessages-' . $userId, $file, true);
    }

    /**
     * Loads flash messages from cache
     * 
     * @return mixed Flash messages
     */
    public function loadFlashMessages() {
        $userId = 0;

        if(isset($_SESSION['userId'])) {
            $userId = $_SESSION['userId'];
        }

        $file = $this->loadCachedFiles('flashMessages-' . $userId, true);
        
        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        return $file;
    }

    /**
     * Invalidates cache in a given namespace
     * 
     * @param string $namespace Namespace name
     * @param bool $flashMessage Is the method called because of flash messages?
     * @return bool True if successful or false if not
     */
    public function invalidateCache(string $namespace, bool $flashMessage = false) {
        global $app;

        if(!$this->logger->getCfg()['ENABLE_CACHING'] && !$flashMessage) {
            return false;
        }

        return FileManager::deleteFolderRecursively($app->cfg['APP_REAL_DIR'] . $app->cfg['CACHE_DIR'] . $namespace . '\\');
    }

    /**
     * Invalidates cache for several given namespaces
     * 
     * @param array $namespaces Array of namespace names
     * @return bool True if all cache namespaces were invalidated successfully or false if not
     */
    public function invalidateCacheBulk(array $namespaces) {
        if(!$this->logger->getCfg()['ENABLE_CACHING']) {
            return null;
        }

        $total = true;
        foreach($namespaces as $namespace) {
            $result = self::invalidateCache($namespace);

            if($total !== false) {
                $total = $result;
            }
        }

        return $total;
    }

    /**
     * Saves page to cache
     * 
     * @param string $moduleName Module name
     * @param string $presenterName Presenter name
     * @param string $content Page content
     * @return bool True if successful or false if not
     */
    public function savePageToCache(string $moduleName, string $presenterName, string $content) {
        $file = $this->loadCachedFiles('cachedPages');

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[$moduleName . '_' . $presenterName] = $content;

        $file = serialize($file);

        return $this->saveCachedFiles('cachedPages', $file);
    }
    
    /**
     * Loads all pages from cache
     * 
     * @return mixed Cache content
     */
    public function loadPagesFromCache() {
        $file = $this->loadCachedFiles('cachedPages');

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        return $file;
    }
}

?>