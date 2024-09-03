<?php

namespace App\UI\GridBuilder;

use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Logger\Logger;

class GridExportHandler {
    private CacheManager $cache;
    private string $hash;
    private ?GridBuilder $gb;

    public function __construct(Logger $logger) {
        $this->cache = new CacheManager($logger);
        $this->gb = null;
        $this->hash = $this->createHash();
    }

    public function setData(GridBuilder $gb) {
        $this->gb = $gb;
    }

    public function saveCache() {
        if($this->gb === null) {
            return false;
        }

        $expire = new DateTime();
        $expire->modify('+1h');

        return $this->cache->saveCache($this->hash, function() {
            return [
                'data' => $this->gb->getDataSourceArray(),
                'columns' => $this->gb->getColumns(),
                'exportCallbacks' => $this->processExportCallbacks()
            ];
        }, CacheManager::NS_GRID_EXPORT_DATA, __METHOD__, $expire);
    }

    public function getHash() {
        return $this->hash;
    }

    private function createHash() {
        return HashManager::createHash(16);
    }

    private function processExportCallbacks() {
        $results = [];
        
        $i = 0;
        foreach($this->gb->getDataSourceArray() as $entity) {
            foreach($this->gb->getExportCallbacks() as $key => $func) {
                $results[$i][$key] = $func($entity);
            }

            $i++;
        }

        return $results;
    }
}

?>