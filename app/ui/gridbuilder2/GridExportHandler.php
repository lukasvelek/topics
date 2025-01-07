<?php

namespace App\UI\GridBuilder2;

use App\Core\Application;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheFactory;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Core\DB\DatabaseRow;
use App\Core\FileManager;
use App\Core\HashManager;
use App\Exceptions\AException;
use App\Exceptions\FileWriteException;
use App\Exceptions\GridExportException;
use Exception;
use QueryBuilder\QueryBuilder;

/**
 * GridExportHandler is responsible for handling grid exports - synchronous or asynchronous
 * 
 * @author Lukas Velek
 */
class GridExportHandler {
    private QueryBuilder $dataSource;
    private string $primaryKey;
    private array $columns;
    private array $columnLabels;
    private ?string $currentUserId;
    private array $cfg;
    private Application $app;
    private string $gridName;
    private int $exportedEntryCount;
    private bool $hasProcessedColumns;

    private CacheFactory $cacheFactory;
    private Cache $exportDataCache;

    /**
     * Class constructor
     * 
     * @param QueryBuilder $dataSource QueryBuilder instance representing grid data source
     * @param array $columns Grid columns
     * @param array $columnLabels Grid column labels
     * @param ?string $currentUserId Current user ID or null
     * @param array $cfg Application configuration
     * @param Application $app Application instance
     * @param string $gridName Grid name
     */
    public function __construct(
        QueryBuilder $dataSource,
        string $primaryKey,
        array $columns,
        array $columnLabels,
        ?string $currentUserId,
        array $cfg,
        Application $app,
        string $gridName
    ) {
        $this->dataSource = $dataSource;
        $this->primaryKey = $primaryKey;
        $this->columns = $columns;
        $this->columnLabels = $columnLabels;
        $this->currentUserId = $currentUserId;
        $this->cfg = $cfg;
        $this->app = $app;
        $this->gridName = $gridName;
        $this->hasProcessedColumns = false;

        $this->cacheFactory = new CacheFactory($this->cfg);
        $this->exportDataCache = $this->cacheFactory->getCache(CacheNames::GRID_EXPORTS);
    }

    /**
     * Destructs $this
     */
    public function __destruct() {
        $this->cacheFactory->__destruct();
    }

    /**
     * Sets if columns have been processed (exported)
     * 
     * @param bool $hasProcessedColumns Has processed columns?
     */
    public function setProcessedColumns(bool $hasProcessedColumns = true) {
        $this->hasProcessedColumns = $hasProcessedColumns;
    }

    /**
     * Sets the number of entries
     * 
     * @param int $entryCount
     */
    public function setEntryCount(int $entryCount) {
        $this->exportedEntryCount = $entryCount;
    }

    /**
     * Processes asynchronous grid export
     * 
     * @return string Grid export hash
     */
    public function exportAsync() {
        try {
            $hash = $this->getHash();

            $this->app->gridExportRepository->beginTransaction();

            $this->app->gridExportRepository->createNewExport($this->currentUserId, $hash, $this->gridName);

            $exportedDataSource = $this->dataSource->export();
            $exportedColumns = $this->processColumnsForAsyncSaveToCache();

            $this->exportDataCache->save($hash, function() use ($exportedDataSource, $exportedColumns) {
                return [
                    'dataSource' => $exportedDataSource,
                    'primaryKey' => $this->primaryKey,
                    'columns' => $exportedColumns,
                    'columnLabels' => $this->columnLabels,
                    'gridName' => $this->gridName,
                    'exportedEntryCount' => $this->exportedEntryCount
                ];
            });

            $this->app->gridExportRepository->commit($this->currentUserId, __METHOD__);

            return $hash;
        } catch(AException|Exception $e) {
            $this->app->gridExportRepository->rollback();

            throw new GridExportException(null, $e);
        }
    }

    /**
     * Processes synchronous export. If $hash is provided then it works with this grid export database entry.
     * 
     * @param ?string $hash Hash or null
     * @return array Exported file path and hash
     */
    public function exportNow(?string $hash = null) {
        try {
            $start = time();
            if ($this->hasProcessedColumns) {
                $data = $this->processProcessedDataSource();
            } else {
                $data = $this->processDataSource();
            }

            $content = $this->createCsvFileContent($data);
            $filePath = $this->saveFile($content);
            
            $this->app->gridExportRepository->beginTransaction(__METHOD__);

            if($hash === null) {
                $hash = $this->getHash();

                $this->app->gridExportRepository->createNewExport($this->currentUserId, $hash, $this->gridName);
            }

            $filePath = str_replace('\\', '/', $filePath);
            $end = time();
            $diff = $end - $start;

            if(!$this->app->gridExportRepository->updateExportByHash($hash, [
                'filename' => $filePath,
                'entryCount' => $this->exportedEntryCount,
                'dateFinished' => DateTime::now(),
                'timeTaken' => $diff
            ])) {
                throw new GridExportException('Could not update entry in the database.');
            }

            $this->app->gridExportRepository->commit($this->currentUserId, __METHOD__);

            return [$filePath, $hash];
        } catch(AException $e) {
            $this->app->gridExportRepository->rollback(__METHOD__);

            throw new GridExportException(null, $e);
        }
    }

    /**
     * Processes already processed data source
     * 
     * @return array Processed data source
     */
    private function processProcessedDataSource() {
        return $this->columns;
    }

    /**
     * Returns generated grid export hash
     * 
     * @return string Grid export hash
     */
    private function getHash() {
        return HashManager::createHash(16, false);
    }

    /**
     * Processes data source for unlimited export
     * 
     * @param QueryBuilder $ds Data source
     * @return QueryBuilder Processed data source
     */
    private function processDataSourceUnlimited(QueryBuilder $ds) {
        $ds->resetLimit();
        $ds->resetOffset();

        return $ds;
    }

    /**
     * Processes data source for limited export
     * 
     * @param bool $unlimited Is this unlimited export?
     * @return array Processed data
     */
    private function processDataSource(bool $unlimited = false) {
        $ds = clone $this->dataSource;
        
        if($unlimited) {
            $ds = $this->processDataSourceUnlimited($ds);
        }

        $cursor = $ds->execute();

        $data = [];
        $i = 0;
        while($row = $cursor->fetchAssoc()) {
            $tmp = [];

            $primaryKey = '';
            foreach($row as $k => $v) {
                $rowObj = DatabaseRow::createFromDbRow($row);

                if(array_key_exists($k, $this->columns)) {
                    $col = $this->columns[$k];

                    if(!empty($col->onExportColumn)) {
                        foreach($col->onExportColumn as $export) {
                            try {
                                $v = $export($rowObj, $v);
                            } catch(Exception $e) {}
                        }
                    }

                    $tmp[$k] = $v;
                }

                $primaryKey = $rowObj->{$this->primaryKey};
            }

            $data[$primaryKey] = $tmp;
            $i++;
        }

        $this->exportedEntryCount = $i;

        return $data;
    }

    /**
     * Creates CSV file content
     * 
     * @param array $data Processed data source
     * @return string CSV file content
     */
    private function createCsvFileContent(array $data) {
        if(empty($data)) {
            throw new GridExportException('No data found.');
        }

        $tmp = [];

        $header = ['#'];

        if($this->hasProcessedColumns) {
            foreach($this->columnLabels as $col) {
                $header[] = $col;
            }
        } else {
            foreach($this->columns as $name => $column) {
                if(array_key_exists($name, $this->columnLabels)) {
                    $header[] = $this->columnLabels[$name];
                } else {
                    $header[] = $name;
                }
            }
        }

        $tmp['header'] = $header;

        foreach($data as $primaryKey => $cols) {
            $x = [$primaryKey];

            foreach($cols as $name => $value) {
                if($value === null) {
                    $value = '-';
                }
                $x[] = $value;
            }

            $tmp[$primaryKey] = $x;
        }

        $content = '';
        foreach($tmp as $row => $cols) {
            $content .= implode(';', $cols) . "\r\n";
        }

        return $content;
    }

    /**
     * Saves the exported data to file
     * 
     * @param string $fileContent CSV file content
     * @return string Filename
     */
    private function saveFile(string $fileContent) {
        $filename = 'GridExport_' . $this->currentUserId . '_' . date('Y-m-d_H-i-s') . '.csv';

        $filepath = $this->cfg['APP_REAL_DIR'] . $this->cfg['GRID_EXPORT_DIR'];

        try {
            $result = FileManager::saveFile($filepath, $filename, $fileContent);

            if($result === false) {
                throw new FileWriteException($filepath . $filename);
            }

            return $this->cfg['GRID_EXPORT_DIR'] . $filename;
        } catch(AException $e) {
            throw $e;
        }
    }

    /**
     * Processes columns to be ready to be saved to cache. For asynchronous export only.
     * 
     * @return array $data Processed columns
     */
    private function processColumnsForAsyncSaveToCache() {
        $ds = clone $this->dataSource;
        
        $ds->resetLimit();
        $ds->resetOffset();

        $cursor = $ds->execute();

        $data = [];
        $i = 0;
        while($row = $cursor->fetchAssoc()) {
            $tmp = [];

            $primaryKey = '';
            foreach($row as $k => $v) {
                $rowObj = DatabaseRow::createFromDbRow($row);

                if(array_key_exists($k, $this->columns)) {
                    $col = $this->columns[$k];

                    if(!empty($col->onExportColumn)) {
                        foreach($col->onExportColumn as $export) {
                            try {
                                $v = $export($rowObj, $v);
                            } catch(Exception $e) {}
                        }
                    }

                    $tmp[$k] = $v;
                }

                $primaryKey = $rowObj->{$this->primaryKey};
            }

            $data[$primaryKey] = $tmp;
            $i++;
        }

        $this->exportedEntryCount = $i;

        return $data;
    }

    /**
     * Creates a GridExportHandler that is called from the background service
     * 
     * @param array $data Data for export
     * @param Application $app Application instance
     * @param array $cfg Application configuration
     * @param ?string $userId Current user ID
     * @return self
     */
    public static function createForAsync(array $data, Application $app, array $cfg, ?string $userId) {
        $dataSource = $data['dataSource'];
        $primaryKey = $data['primaryKey'];
        $columns = $data['columns'];
        $columnLabels = $data['columnLabels'];
        $gridName = $data['gridName'];
        $exportedEntryCount = $data['exportedEntryCount'];

        $qb = $app->gridExportRepository->getQb();
        $qb = $qb->import($dataSource);

        $obj = new self($qb, $primaryKey, $columns, $columnLabels, $userId, $cfg, $app, $gridName);
        $obj->setProcessedColumns();
        $obj->setEntryCount($exportedEntryCount);

        return $obj;
    }
}

?>