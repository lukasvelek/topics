<?php

namespace App\Core\Caching\Memory;

use App\Core\HashManager;
use Exception;

/**
 * Memory cache is held in memory until next request
 * 
 * @author Lukas Velek
 */
class Cache {
    private array $data;
    private string $namespace;
    private string $hash;

    /**
     * Class constructor
     * 
     * @param string $namespace Namespace
     */
    public function __construct(string $namespace) {
        $this->namespace = $namespace;
        
        $this->data = [];
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
                return null;
            }

            $this->data[$key] = $result;

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
        } catch(Exception) {}

        $this->data[$key] = $result;
    }

    /**
     * Invalidates cache
     */
    public function invalidate() {
        $this->data = [];
    }

    /**
     * Returns hash
     * 
     * @return string Hash
     */
    public function getHash() {
        return $this->hash;
    }
}

?>