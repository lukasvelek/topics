<?php

namespace App\UI\GridBuilder;

use App\Core\CacheManager;
use App\Core\Datatypes\ArrayList;
use App\Core\FileManager;
use App\Logger\Logger;

class GridExporter {
    private string $hash;
    private CacheManager $cache;
    private ArrayList $dataToSave;
    private array $cfg;

    public function __construct(Logger $logger, string $hash, array $cfg) {
        $this->cache = new CacheManager($logger);
        $this->hash = $hash;
        $this->dataToSave = new ArrayList();
        $this->cfg = $cfg;
    }

    public function export() {
        $data = $this->loadCache();

        if(empty($data)) {
            return null;
        }

        $columns = $data['columns'];
        //$prebuilt = $data['prebuild'];
        $data = $data['data'];

        $this->addLine($columns);

        foreach($data as $entity) {
            $tmp = [];
            foreach($columns as $column => $title) {
                $objectVarName = ucfirst($column);
                $objectIdVarName = $objectVarName . 'Id';

                $result = '';
                if(method_exists($entity, 'get' . $objectVarName)) {
                    $result = $entity->{'get' . $objectVarName}();
                } else if(method_exists($entity, 'is' . $objectVarName)) {
                    $result = $entity->{'is' . $objectVarName}();
                } else if(isset($entity->$column)) {
                    $result = $entity->$column;
                } else if(method_exists($entity, 'get' . $objectIdVarName)) {
                    $result = $entity->{'get' . $objectIdVarName}();
                }

                $tmp[] = $result;
            }

            $this->addLine($tmp);
        }

        return $this->saveFile();
    }

    private function loadCache() {
        return $this->cache->loadCache($this->hash, function() {
            return [];
        }, CacheManager::NS_GRID_EXPORT_DATA, __METHOD__);
    }

    private function addLine(array $parts) {
        if(empty($parts)) {
            return;
        }
        $text = implode(';', $parts);
        $this->dataToSave->add(null, $text);
        $this->newLine();
    }

    private function newLine() {
        $this->dataToSave->add(null, "\r\n");
    }

    private function saveFile() {
        $path = $this->cfg['APP_REAL_DIR'] . $this->cfg['CACHE_DIR'] . CacheManager::NS_GRID_EXPORTS . '\\';
        $name = 'gridExport_' . $this->hash . '.csv';

        if(FileManager::saveFile($path, $name, $this->dataToSave->getAll(), false, false) !== false) {
            return 'cache\\' . CacheManager::NS_GRID_EXPORTS . '\\' . $name;
        } else {
            return null;
        }
    }
}

?>