<?php

namespace App\Core\Caching;

use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Exceptions\CacheException;
use Exception;

/**
 * Persistent cache that is loaded and held in memory until it's content is saved to disk
 * 
 * @author Lukas Velek
 */
class Cache {
    private array $data;
    private ?DateTime $expirationDate;
    private bool $invalidated;
    private string $hash;
    private string $namespace;
    private ?DateTime $lastWriteDate;
    private CacheFactory $cacheFactory;

    /**
     * Class constructor
     * 
     * @param array $data Loaded data
     * @param string $namespace Namespace
     * @param ?DateTime $expirationDate Cache expiration date
     * @param ?DateTime $lastWriteDate Date of last write
     */
    public function __construct(array $data,  string $namespace, CacheFactory $cacheFactory, ?DateTime $expirationDate = null, ?DateTime $lastWriteDate = null) {
        $this->data = $data;
        $this->expirationDate = $expirationDate;
        $this->invalidated = false;
        $this->namespace = $namespace;
        $this->lastWriteDate = $lastWriteDate;
        $this->cacheFactory = $cacheFactory;

        $this->hash = HashManager::createHash(256);
    }

    /**
     * Loads data from cache
     * 
     * @param mixed $key Data key
     * @param callback $generator Data generator
     * @param array $generatorDependencies Data generator dependencies (arguments)
     * @return mixed|null Data or null
     */
    public function load(mixed $key, callable $generator, array $generatorDependencies = []) {
        if(array_key_exists($key, $this->data)) {
            return $this->data[$key];
        } else {
            try {
                $result = $generator(...$generatorDependencies);
            } catch(Exception $e) {
                throw new CacheException('Could not save data to cache.', $this->namespace, $e);
            }

            $this->data[$key] = $result;
            $this->lastWriteDate = new DateTime();

            return $result;
        }
    }

    /**
     * Saves data to cache
     * 
     * @param mixed $key Data key
     * @param callback $generator Data generator
     * @param array $generatorDependencies Data generator dependencies (arguments)
     * @return void
     */
    public function save(mixed $key, callable $generator, array $generatorDependencies = []) {
        try {
            $result = $generator(...$generatorDependencies);
        } catch(Exception $e) {
            throw new CacheException('Could not save data to cache.', $this->namespace, $e);
        }

        $this->data[$key] = $result;

        $this->lastWriteDate = new DateTime();
    }

    /**
     * Invalidates cache
     */
    public function invalidate() {
        $this->data = [];
        $this->invalidated = true;
        $this->cacheFactory->invalidateCacheByCache($this);
    }

    /**
     * Invalidates a single key in cache
     * 
     * @param mixed $key Cache key
     */
    public function invalidateKey(mixed $key) {
        if(array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
        }
    }

    /**
     * Returns hash
     * 
     * @return string Hash
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * Returns data
     * 
     * @return array Data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Returns last write date
     * 
     * @return DateTime Last write date
     */
    public function getLastWriteDate() {
        return $this->lastWriteDate;
    }

    /**
     * Returns expiration date
     * 
     * @return DateTime Expiration date
     */
    public function getExpirationDate() {
        return $this->expirationDate;
    }

    /**
     * Is cache invalidated?
     * 
     * @return bool True if invalidated or false if not
     */
    public function isInvalidated() {
        return $this->invalidated;
    }

    /**
     * Returns namespace
     * 
     * @return string Namespace
     */
    public function getNamespace() {
        return $this->namespace;
    }
}

?>