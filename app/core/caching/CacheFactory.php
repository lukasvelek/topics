<?php

namespace App\Core\Caching;

use App\Core\Caching\Persistent\Cache;
use App\Core\Datetypes\DateTime;
use App\Core\FileManager;

class CacheFactory {
    private const I_NS_DATA = '_data';
    private const I_NS_CACHE_EXPIRATION = '_cacheExpirationDate';
    private const I_NS_CACHE_LAST_WRITE_DATE = '_cacheLastWriteDate';

    private array $cfg;

    /** @var array<Cache> */
    private array $persistentCaches;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;
        $this->persistentCaches = [];
    }

    public function getPersistentCache(string $namespace) {
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

    private function loadFileContent(string $path, string $filename) {
        if(!FileManager::fileExists($path . $filename)) {
            return null;
        }

        return FileManager::loadFile($path . $filename);
    }

    public function savePersistentCaches() {
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

    private function saveDataToPersistentCache(string $namespace, array $data) {
        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['CACHE_DIR'] . $namespace . '\\';
        
        $date = new DateTime();
        $date->format('Y-m-d');

        $filename = $date . $namespace;
        $filename = md5($filename);

        return FileManager::saveFile($path, $filename, serialize($data), true);
    }

    private function deletePersistentCache(string $namespace) {
        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['CACHE_DIR'] . $namespace . '\\';

        return FileManager::deleteFolderRecursively($path);
    }
}

?>