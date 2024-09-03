<?php

namespace App\UI\GridBuilder;

use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Logger\Logger;

/**
 * Handler for grid exports
 * 
 * It saves all necessary data to cache for the AJAX callback to create export.
 * 
 * @author Lukas Velek
 */
class GridExportHandler {
    private CacheManager $cache;
    private string $hash;
    private ?GridBuilder $gb;
    private array $dataAll;

    /**
     * Class constructor
     * 
     * @param ?Logger $logger Logger instance
     */
    public function __construct(?Logger $logger = null) {
        $this->cache = new CacheManager($logger);
        $this->gb = null;
        $this->hash = $this->createHash();
        $this->dataAll = [];
    }

    /**
     * Sets the GridBuilder instance for data retrieving purposes
     * 
     * @param GridBuilder $gb GridBuilder instance
     */
    public function setData(GridBuilder $gb) {
        $this->gb = $gb;
    }

    /**
     * Sets all the data for the export
     */
    public function setDataAll(array $dataAll) {
        $this->dataAll = $dataAll;
    }

    /**
     * Saves data to cache
     * 
     * @return bool True if successful or false if not
     */
    public function saveCache() {
        if($this->gb === null) {
            return false;
        }

        $expire = new DateTime();
        $expire->modify('+1h');

        return $this->cache->saveCache($this->hash, function() {
            $result = [
                'data' => $this->gb->getDataSourceArray(),
                'columns' => $this->gb->getColumns(),
                'exportCallbacks' => $this->processExportCallbacks()
            ];

            if(!empty($this->dataAll)) {
                $result['dataAll'] = $this->dataAll;
            }

            return $result;
        }, CacheManager::NS_GRID_EXPORT_DATA, __METHOD__, $expire);
    }

    /**
     * Returns generated hash
     * 
     * @return string Generated hash
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * Generates hash
     * 
     * @return string Generated hash
     */
    private function createHash() {
        return HashManager::createHash(16);
    }

    /**
     * Processes export callbacks
     * 
     * It goes through all export callbacks and saves their result to an array of arrays. E.g.: [0 => [name => test, password => test], 1 => [name => test2, password => test2]].
     * The first array is indexed by the entity position and the values are arrays that have indexes equal to the column names and values equal to the displayed value.
     * 
     * @return array<int,array> Processed export callbacks
     */
    private function processExportCallbacks() {
        $results = [];

        $dataArray = $this->gb->getDataSourceArray();

        if(!empty($this->dataAll)) {
            $dataArray = $this->dataAll;
        }
        
        $i = 0;
        foreach($dataArray as $entity) {
            foreach($this->gb->getExportCallbacks() as $key => $func) {
                $results[$i][$key] = $func($entity);
            }

            $i++;
        }

        return $results;
    }
}

?>