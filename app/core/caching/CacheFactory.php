<?php

namespace App\Core\Caching;

use App\Core\Caching\Cache;
use App\Core\Datetypes\DateTime;
use App\Core\FileManager;

/**
 * CacheFactory allows creating cache
 * 
 * @author Lukas Velek
 */
class CacheFactory {
    private const I_NS_DATA = '_data';
    private const I_NS_CACHE_EXPIRATION = '_cacheExpirationDate';
    private const I_NS_CACHE_LAST_WRITE_DATE = '_cacheLastWriteDate';

    private array $cfg;

    /** @var array<Cache> */
    private array $persistentCaches;

    /**
     * Class constructor
     * 
     * @param array $cfg Cofiguration
     */
    public function __construct(array $cfg) {
        $this->cfg = $cfg;
        $this->persistentCaches = [];
    }

    /**
     * Class destructor
     */
    public function __destruct() {
        $this->saveCaches();

        $this->persistentCaches = [];
    }

    /**
     * Returns a new instance of persistance cache
     * 
     * If persistent cache with given namespace already exists, the data is loaded and passed to the instance.
     * 
     * @param string $namespace Namespace
     * @return Cache Persistent cache
     */
    public function getCache(string $namespace) {
        $cacheData = $this->loadDataFromPersistentCache($namespace);

        if($cacheData === null) {
            $cache = new Cache([], $namespace, null, null);
            $this->persistentCaches[$cache->getHash()] = &$cache;
            return $cache;
        }

        $expirationDate = null;
        if(isset($cacheData[self::I_NS_CACHE_EXPIRATION])) {
            $expirationDate = new DateTime(strtotime($cacheData[self::I_NS_CACHE_EXPIRATION]));
        }

        $lastWriteDate = null;
        if(isset($cacheData[self::I_NS_CACHE_LAST_WRITE_DATE])) {
            $lastWriteDate = new DateTime(strtotime($cacheData[self::I_NS_CACHE_LAST_WRITE_DATE]));
        }

        $cache = new Cache($cacheData[self::I_NS_DATA], $namespace, $expirationDate, $lastWriteDate);
        $this->persistentCaches[$cache->getHash()] = &$cache;
        return $cache;
    }

    /**
     * Loads data from persistent cache
     * 
     * @param string $namespace Namespace
     * @return mixed|null Loaded content or null
     */
    private function loadDataFromPersistentCache(string $namespace) {
        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['CACHE_DIR'] . $namespace . '\\';
        
        $date = new DateTime();
        $date->format('Y-m-d');

        $filename = $date . $namespace;
        $filename = md5($filename);

        $content = $this->loadFileContent($path, $filename);

        if($content === null) {
            return $content;
        }

        $content = unserialize($content);

        return $content;
    }

    /**
     * Loads file content
     * 
     * @param string $path Path
     * @param string $filename Filename
     * @return string|null File content or null if file does not exist
     */
    private function loadFileContent(string $path, string $filename) {
        if(!FileManager::fileExists($path . $filename)) {
            return null;
        }

        $content = FileManager::loadFile($path . $filename);

        if($content === false) {
            return null;
        }

        return $content;
    }

    /**
     * Saves persistent caches
     */
    public function saveCaches() {
        foreach($this->persistentCaches as $cache) {
            if($cache->isInvalidated()) {
                $this->deletePersistentCache($cache->getNamespace());
            } else {
                $tmp = [
                    self::I_NS_DATA => $cache->getData(),
                    self::I_NS_CACHE_EXPIRATION => $cache->getExpirationDate()?->getResult(),
                    self::I_NS_CACHE_LAST_WRITE_DATE => $cache->getLastWriteDate()?->getResult()
                ];

                $this->saveDataToPersistentCache($cache->getNamespace(), $tmp);
            }
        }
    }

    /**
     * Save persistent cache to disk
     * 
     * @param string $namespace Namespace
     * @param array $data Persistent cache data
     * @return int|false Number of bytes written or false if error occured
     */
    private function saveDataToPersistentCache(string $namespace, array $data) {
        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['CACHE_DIR'] . $namespace . '\\';
        
        $date = new DateTime();
        $date->format('Y-m-d');

        $filename = $date . $namespace;
        $filename = md5($filename);

        return FileManager::saveFile($path, $filename, serialize($data), true);
    }

    /**
     * Deletes persistent cache
     * 
     * @param string $namespace Namespace
     * @return bool True on success or false on failure
     */
    private function deletePersistentCache(string $namespace) {
        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['CACHE_DIR'] . $namespace . '\\';

        return FileManager::deleteFolderRecursively($path);
    }
}

?>