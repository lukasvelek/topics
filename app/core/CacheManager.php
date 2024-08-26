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
    public const NS_TOPICS = 'topics';
    public const NS_POSTS = 'posts';
    public const NS_GROUP_MEMBERSHIPS = 'groupMemberships';
    public const NS_GRID_PAGE_DATA = 'gridPageData';
    public const NS_TOPIC_MEMBERSHIPS = 'topicMemberships';
    public const NS_GROUPS = 'groups';
    public const NS_USERS = 'users';
    public const NS_USERS_USERNAME_TO_ID_MAPPING = 'usersUsernameToIdMapping';
    public const NS_FLASH_MESSAGES = 'flashMessages';
    public const NS_CACHED_PAGES = 'cachedPages';
    public const NS_PINNED_POSTS = 'pinnedPosts';
    public const NS_USER_NOTIFICATIONS = 'userNotifications';

    private const I_NS_DATA = '_data';
    private const I_NS_EXPIRATION_DATE = '_cacheDateExpiration';

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

        if(!$this->isCacheForNamespaceCommon($namespace)) {
            $userId = $this->getCurrentUserId();

            if($userId !== null) {
                $filename .= $userId;
            }
        }

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
    public function loadCache(mixed $key, callable $callback, string $namespace = 'default', ?string $method = null, ?DateTime $expiration = null) {
        $file = $this->loadCachedFiles($namespace);
        $save = false;
        $result = null;
        $cacheHit = true;

        if($file === null) {
            $result = $callback();
            $file[self::I_NS_DATA][$key] = $result;
            if($expiration !== null) {
                $expiration = $expiration->getResult();
            }
            $file[self::I_NS_EXPIRATION_DATE] = $expiration;
            $save = true;
        } else {
            $file = unserialize($file);

            $isCacheExpired = $this->checkCacheExpiration($file);

            if(array_key_exists($key, $file[self::I_NS_DATA]) && !$isCacheExpired) {
                $result = $file[self::I_NS_DATA][$key];
            } else {
                $result = $callback();
                $file[self::I_NS_DATA][$key] = $result;
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
    public function saveCache(mixed $key, callable $callback, string $namespace = 'default', ?string $method = null, ?DateTime $expiration = null) {
        $file = $this->loadCachedFiles($namespace);
        
        if($file === null) {
            $file = [self::I_NS_DATA];
        } else {
            $file = unserialize($file);
        }
        
        $result = $callback();
        $file[self::I_NS_DATA][$key] = $result;
        if($expiration !== null) {
            $expiration = $expiration->getResult();
        }
        $file[self::I_NS_EXPIRATION_DATE] = $expiration;

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
        $this->invalidateCache('flashMessages', true);
    }

    /**
     * Saves a flash message to cache
     * 
     * @param array $data Flash message data
     * @return bool True if successful or false if not
     */
    public function saveFlashMessageToCache(array $data) {
        $file = $this->loadCachedFiles('flashMessages', true);

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[self::I_NS_DATA][] = $data;

        $file = serialize($file);

        return $this->saveCachedFiles('flashMessages', $file, true);
    }

    /**
     * Loads flash messages from cache
     * 
     * @return mixed Flash messages
     */
    public function loadFlashMessages() {
        $file = $this->loadCachedFiles('flashMessages', true);
        
        if($file !== null && $file !== false) {
            $file = unserialize($file)[self::I_NS_DATA];
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
        $file = $this->loadCachedFiles(self::NS_CACHED_PAGES);

        if($file !== null && $file !== false) {
            $file = unserialize($file);
        }

        $file[self::I_NS_DATA][$moduleName . '_' . $presenterName] = $content;

        $file = serialize($file);

        return $this->saveCachedFiles(self::NS_CACHED_PAGES, $file);
    }
    
    /**
     * Loads all pages from cache
     * 
     * @return mixed Cache content
     */
    public function loadPagesFromCache() {
        $file = $this->loadCachedFiles(self::NS_CACHED_PAGES);

        if($file !== null && $file !== false) {
            $file = unserialize($file)[self::I_NS_DATA];
        }

        return $file;
    }

    /**
     * Returns the ID of the current user
     * 
     * @return string|null User ID or null if no current user is defined
     */
    private function getCurrentUserId(): string|null {
        if(isset($_SESSION['userId'])) {
            return $_SESSION['userId'];
        } else {
            global $app;

            if($app->currentUser !== null) {
                return $app->currentUser->getId();
            } else {
                return null;
            }
        }
    }

    /**
     * Checks if cache namespace is common or per-user
     * 
     * @param string $namespace Namespace name
     * @return bool True if namespace is common or false if it is per-user
     */
    private function isCacheForNamespaceCommon(string $namespace) {
        return !in_array($namespace, [self::NS_FLASH_MESSAGES]);
    }

    /**
     * Checks if cache in the given namespace is expired or not
     * 
     * @param array $cacheFileContent Unserialized file content of cache
     * @return bool True if cache is expired or false if not
     */
    private function checkCacheExpiration(array $cacheFileContent) {
        if(isset($data[self::I_NS_EXPIRATION_DATE])) {
            $expirationDate = $data[self::I_NS_EXPIRATION_DATE];

            if($expirationDate !== null) {
                if(strtotime($expirationDate) < time()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns expiration DateTime instance adjusted by given number of minutes
     * 
     * @param int $minutes Number of minutes to be added to the final date
     * @return DateTime
     */
    public static function EXPIRATION_MINUTES(int $minutes = 1) {
        $dt = new DateTime();
        $dt->modify('+' . $minutes . 'i');
        return $dt;
    }

    /**
     * Returns expiration DateTime instance adjusted by given number of hours
     * 
     * @param int $hours Number of hours to be added to the final date
     * @return DateTime
     */
    public static function EXPIRATION_HOURS(int $hours = 1) {
        $dt = new DateTime();
        $dt->modify('+'  . $hours . 'h');
        return $dt;
    }
}

?>