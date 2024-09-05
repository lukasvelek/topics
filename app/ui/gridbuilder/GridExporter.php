<?php

namespace App\UI\GridBuilder;

use App\Core\CacheManager;
use App\Core\Datatypes\ArrayList;
use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use App\Logger\Logger;

/**
 * GridExporter exports grid data and saves it to CSV
 * 
 * @author Lukas Velek
 */
class GridExporter {
    private string $hash;
    private CacheManager $cache;
    private ArrayList $dataToSave;
    private array $cfg;
    private bool $exportAll;
    private int $exportedEntries;

    /**
     * Class constructor
     * 
     * @param ?Logger $logger Logger instance
     * @param string $hash Grid export hash
     * @param array $cfg Application configuration array
     */
    public function __construct(?Logger $logger, string $hash, array $cfg) {
        $this->cache = new CacheManager($logger);
        $this->hash = $hash;
        $this->dataToSave = new ArrayList();
        $this->cfg = $cfg;
        $this->exportAll = false;
        $this->exportedEntries = 0;
    }

    public function setExportAll(bool $exportAll = true) {
        $this->exportAll = $exportAll;
    }

    /**
     * Exports the grid data and returns the filename
     * 
     * @return string|null Generated filename or null if not successful
     */
    public function export() {
        $data = $this->loadCache();

        if(empty($data)) {
            return null;
        }

        $columns = $data['columns'];
        $exportCallbacks = $data['exportCallbacks'];

        if($this->exportAll) {
            $data = $data['dataAll'];
        } else {
            $data = $data['data'];
        }

        $this->addLine($columns);

        $i = 0;
        foreach($data as $entity) {
            $tmp = [];
            foreach($columns as $column => $title) {
                $objectVarName = ucfirst($column);
                $objectIdVarName = $objectVarName . 'Id';

                $result = '';
                if(isset($exportCallbacks[$i][$column])) {
                    $result = $exportCallbacks[$i][$column];
                } else if(method_exists($entity, 'get' . $objectVarName)) {
                    $result = $entity->{'get' . $objectVarName}();
                } else if(method_exists($entity, 'is' . $objectVarName)) {
                    $result = $entity->{'is' . $objectVarName}();
                } else if(isset($entity->$column)) {
                    $result = $entity->$column;
                } else if(method_exists($entity, 'get' . $objectIdVarName)) {
                    $result = $entity->{'get' . $objectIdVarName}();
                }

                $result = trim($result);
                $result = str_replace("\r\n", '', $result);

                $tmp[] = $result;
            }

            $this->addLine($tmp);

            $i++;
        }

        $this->exportedEntries = count($data);

        return $this->saveFile();
    }

    /**
     * Loads grid export data from cache
     * 
     * @return array Grid data
     */
    private function loadCache() {
        return $this->cache->loadCache($this->hash, function() {
            return [];
        }, CacheManager::NS_GRID_EXPORT_DATA, __METHOD__);
    }

    /**
     * Adds new line to the CSV temp
     * 
     * @param array $parts Single-line parts that will be connected with ";"
     * @return void
     */
    private function addLine(array $parts) {
        if(empty($parts)) {
            return;
        }
        $text = implode(';', $parts);
        $this->dataToSave->add(null, $text);
        $this->dataToSave->add(null, "\r\n");
    }

    /**
     * Saves file to the cache directory
     * 
     * @return string|null Filename if successful or null if not
     */
    private function saveFile() {
        $now = new DateTime();
        $now->format('Y-m-d_H-i-s');
        $now = $now->getResult();

        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['CACHE_DIR'] . CacheManager::NS_GRID_EXPORTS . '\\';
        $name = 'gridExport_' . $this->hash . '_' . $now . '.csv';

        if(FileManager::saveFile($path, $name, $this->dataToSave->getAll(), false, false) !== false) {
            return 'cache\\' . CacheManager::NS_GRID_EXPORTS . '\\' . $name;
        } else {
            return null;
        }
    }

    /**
     * Returns the number of exported entries
     * 
     * @return int Number of exported entries
     */
    public function getEntryCount() {
        return $this->exportedEntries;
    }
}

?>