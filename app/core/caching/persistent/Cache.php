<?php

namespace App\Core\Caching\Persistent;

use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use Exception;

class Cache {
    private array $data;
    private ?DateTime $expirationDate;
    private bool $invalidated;
    private string $hash;
    private string $namespace;
    private ?DateTime $lastWriteDate;

    public function __construct(array $data, ?DateTime $expirationDate = null, ?DateTime $lastWriteDate, string $namespace) {
        $this->data = $data;
        $this->expirationDate = $expirationDate;
        $this->invalidated = false;
        $this->namespace = $namespace;
        $this->lastWriteDate = $lastWriteDate;

        $this->hash = HashManager::createHash(256);
    }

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
            $this->lastWriteDate = new DateTime();

            return $result;
        }
    }

    public function save(mixed $key, callable $generator, array $generatorDependencies = []) {
        try {
            $result = $generator(...$generatorDependencies);
        } catch(Exception) {}

        $this->data[$key] = $result;

        $this->lastWriteDate = new DateTime();
    }

    public function invalidate() {
        $this->data = [];
        $this->invalidated = true;
    }

    public function getHash() {
        return $this->hash;
    }

    public function getData() {
        return $this->data;
    }

    public function getLastWriteDate() {
        return $this->lastWriteDate;
    }

    public function getExpirationDate() {
        return $this->expirationDate;
    }

    public function isInvalidated() {
        return $this->invalidated;
    }

    public function getNamespace() {
        return $this->namespace;
    }
}

?>