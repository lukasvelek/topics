<?php

namespace App\Components\Grid;

use App\Core\HashManager;
use App\Entities\ICreatableFromRow;
use App\UI\GridBuilder\GridBuilder;
use App\UI\IRenderable;
use QueryBuilder\QueryBuilder;

class GridFactory implements IRenderable {
    private string $gridId;
    private ?QueryBuilder $dataSource;
    private ?GridBuilder $grid;
    private ?ICreatableFromRow $gridRowEntity;
    private array $columnTitles;

    public function __construct() {
        $this->gridId = $this->generateGridId();
        $this->dataSource = null;
        $this->grid = null;
        $this->gridRowEntity = null;
        $this->columnTitles = [];
    }

    public function setColumnTitle(string $column, string $title) {
        $this->columnTitles[$column] = $title;
    }

    public function setDataSource(QueryBuilder $dataSource) {
        $this->dataSource = $dataSource;
    }

    public function setEntity(ICreatableFromRow $entity) {
        $this->gridRowEntity = $entity;
    }

    private function generateGridId() {
        return 'grid-' . HashManager::createHash(8, false);
    }

    public function render() {
        $this->startup();

        return $this->grid->build();
    }

    private function startup() {
        if($this->dataSource === null) {
            return null;
        }
        if($this->gridRowEntity === null) {
            return null;
        }

        $this->grid = new GridBuilder();
        $this->grid->addColumns($this->getColumnsFromDataSource());
        $this->grid->addDataSource($this->processDataSource());
        $this->grid->setIdElement($this->gridId);
    }

    private function processDataSource() {
        $cursor = $this->dataSource->execute();

        $entities = [];
        while($row = $cursor->fetchAssoc()) {
            $e = $this->gridRowEntity::createEntityFromDbRow($row);

            if($e !== null) {
                $entities[] = $e;
            }
        }

        return $entities;
    }

    private function getColumnsFromDataSource() {
        $qb = clone($this->dataSource);

        $cursor = $qb->execute();

        $columns = [];
        while($row = $cursor->fetchAssoc()) {
            if(empty($columns)) {
                $columns = array_keys($row);
            }
        }

        $tmp = [];
        foreach($columns as $c) {
            if(array_key_exists($c, $this->columnTitles)) {
                $tmp[$c] = $this->columnTitles[$c];
            } else {
                $tmp[$c] = $c;
            }
        }
        $columns = $tmp;

        return $columns;
    }
}

?>