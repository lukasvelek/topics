<?php

namespace App\UI\GridBuilder2;

use App\Core\DB\DatabaseRow;
use App\Core\FileManager;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;
use App\Modules\TemplateObject;
use App\UI\IRenderable;
use Exception;
use QueryBuilder\QueryBuilder;

class GridBuilder implements IRenderable {
    private ?QueryBuilder $dataSource;
    private string $primaryKeyColName;
    private HttpRequest $httpRequest;
    private array $cfg;
    /**
     * @var array<string, Column> $columns
     */
    private array $columns;
    private array $columnLabels;
    private Table $table;

    public function __construct(HttpRequest $request, array $cfg) {
        $this->httpRequest = $request;
        $this->cfg = $cfg;

        $this->dataSource = null;
        $this->columns = [];
        $this->columnLabels = [];
    }

    public function addColumn(string $name, ?string $label = null) {
        $col = new Column($name);
        $this->columns[$name] = &$col;
        if($label !== null) {
            $this->columnLabels[$name] = $label;
        } else {
            $this->columnLabels[$name] = $name;
        }
        return $col;
    }

    public function createDataSourceFromQueryBuilder(QueryBuilder $qb, string $primaryKeyColName) {
        $this->primaryKeyColName = $primaryKeyColName;
        $this->dataSource = $this->processQueryBuilderDataSource($qb);
    }

    private function processQueryBuilderDataSource(QueryBuilder $qb) {
        $gridSize = $this->cfg['GRID_SIZE'];
        $page = (int)$this->httpRequest->query['page'];

        $qb->limit($gridSize)
            ->offset(($page * $gridSize));

        return $qb;
    }

    public function render() {
        $this->build();

        $template = $this->getTemplate();

        return $template->render();
    }

    private function getTemplate() {
        $content = FileManager::loadFile(__DIR__ . '/grid.html');
        
        return new TemplateObject($content);
    }

    private function build() {
        if($this->dataSource === null) {
            throw new GeneralException('No data source is set.');
        }

        $cursor = $this->dataSource->execute();

        $_tableRows = [];

        $_headerRow = new Row();
        foreach($this->columns as $colName => $colEntity) {
            $_headerCell = new Cell();
            $_headerCell->setName($colName);
            $_headerCell->setContent($this->columnLabels[$colName]);
            $_headerCell->setHeader();
            $_headerRow->addCell($_headerCell);
        }

        $_tableRows[] = $_headerRow;

        while($row = $cursor->fetchAssoc()) {
            $row = $this->createDatabaseRow($row);
            $_row = new Row();
            $_row->setPrimaryKey($row->{$this->primaryKeyColName});

            foreach($row->getKeys() as $k) {
                if(array_key_exists($k, $this->columns)) {
                    $_cell = new Cell();
                    $_cell->setName($k);

                    $content = $row->$k;
                    
                    if(!empty($this->columns[$k]->onRenderColumn)) {
                        foreach($this->columns[$k]->onRenderColumn as $render) {
                            try {
                                $content = $render($row, $_row, $_cell, $content);
                            } catch(Exception $e) {}
                        }
                    }

                    $_cell->setContent($content);

                    $_row->addCell($_cell);
                }
            }

            $_tableRows[] = $_row;
        }

        $this->table = new Table($_tableRows);
    }

    private function createDatabaseRow(mixed $row) {
        $r = new DatabaseRow();

        foreach($row as $k => $v) {
            $r->$k = $v;
        }

        return $r;
    }
}

?>