<?php

namespace App\UI\GridBuilder2;

use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\FileManager;
use App\Core\Http\HttpRequest;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\Modules\TemplateObject;
use App\UI\IRenderable;
use App\UI\LinkBuilder;
use Exception;
use QueryBuilder\QueryBuilder;

class GridBuilder implements IRenderable {
    private const COL_TYPE_TEXT = 'text';
    private const COL_TYPE_DATETIME = 'datetime';

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
    private bool $enablePagination;
    private string $componentName;
    private APresenter $presenter;
    private int $gridPage;

    public function __construct(HttpRequest $request, array $cfg) {
        $this->httpRequest = $request;
        $this->cfg = $cfg;

        $this->dataSource = null;
        $this->columns = [];
        $this->columnLabels = [];
        $this->enablePagination = true;
        $this->gridPage = $this->getGridPage();
    }

    public function setPresenter(APresenter $presenter) {
        $this->presenter = $presenter;
    }

    public function setComponentName(string $name) {
        $this->componentName = $name;
    }

    public function enablePagination() {
        $this->enablePagination = true;
    }

    public function disablePagination() {
        $this->enablePagination = false;
    }

    public function addColumnDatetime(string $name, ?string $label = null) {
        return $this->addColumn($name, self::COL_TYPE_DATETIME, $label);
    }

    public function addColumnText(string $name, ?string $label = null) {
        return $this->addColumn($name, self::COL_TYPE_TEXT, $label);
    }

    private function addColumn(string $name, string $type, ?string $label = null) {
        $col = new Column($name);
        $this->columns[$name] = &$col;
        if($label !== null) {
            $this->columnLabels[$name] = $label;
        } else {
            $this->columnLabels[$name] = $name;
        }

        if($type == self::COL_TYPE_DATETIME) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, mixed $value) {
                return DateTimeFormatHelper::formatDateToUserFriendly($value);
            };

            $col->onExportColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, mixed $value) {
                return DateTimeFormatHelper::formatDateToUserFriendly($value);
            };
        }

        return $col;
    }

    public function createDataSourceFromQueryBuilder(QueryBuilder $qb, string $primaryKeyColName) {
        $this->primaryKeyColName = $primaryKeyColName;
        $this->dataSource = $qb;
    }

    private function processQueryBuilderDataSource(QueryBuilder $qb) {
        $gridSize = $this->cfg['GRID_SIZE'];

        $qb->limit($gridSize)
            ->offset(($this->gridPage * $gridSize));

        return $qb;
    }

    public function render() {
        $this->build();

        $template = $this->getTemplate();

        $template->grid = $this->table->output();
        $template->controls = $this->createGridControls();

        return $template->render()->getRenderedContent();
    }

    private function getTemplate() {
        $content = FileManager::loadFile(__DIR__ . '/grid.html');
        
        return new TemplateObject($content);
    }

    private function build() {
        if($this->dataSource === null) {
            throw new GeneralException('No data source is set.');
        }

        $dataSource = clone $this->dataSource;

        $this->processQueryBuilderDataSource($dataSource);

        $cursor = $dataSource->execute();

        $_tableRows = [];

        $_headerRow = new Row();
        $_headerRow->setPrimaryKey('header');
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

                    if($content === null) {
                        $content = '-';
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

    private function createGridControls() {
        if(!$this->enablePagination) {
            return '';
        }

        $code = '
            <div class="row">
                <div class="col-md">
                    ' . $this->createGridPagingControl() . '
                </div>

                <div class="col-md">
                    ' . $this->createGridRefreshControl() . '
                </div>
            </div>

            <div>
                ' . $this->createScripts() . '
            </div>
        ';

        return $code;
    }

    private function createScripts() {
        $scripts = [];

        // REFRESH
        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setComponentAction($this->presenter, $this->componentName . '-refresh')
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName($this->componentName . '_gridRefresh')
            ->setFunctionArguments(['_page'])
            ->updateHTMLElement('grid-content', 'grid')
            ->setComponent()
        ;

        $scripts[] = '<script type="text/javascript">' . $arb->build() . '</script>';

        // PAGINATION
        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setComponentAction($this->presenter, $this->componentName . '-page')
            ->setHeader(['gridPage' => '_page'])
            ->setFunctionName($this->componentName . '_page')
            ->setFunctionArguments(['_page'])
            ->updateHTMLElement('grid-content', 'grid')
            ->setComponent()
        ;

        $scripts[] = '<script type="text/javascript">' . $arb->build() . '</script>';

        return implode('', $scripts);
    }

    private function createGridRefreshControl() {
        return '<a class="post-data-link" href="#" onclick="' . $this->componentName . '_gridRefresh(' . $this->getGridPage() . ')">Refresh</a>';
    }

    private function createGridPagingControl() {
        $totalCount = $this->getTotalCount();
        $lastPage = ceil($totalCount / $this->cfg['GRID_SIZE']);

        $firstPageBtn = $this->createPagingButtonCode(0, '&lt;&lt;');

        return $firstPageBtn . '';
    }

    private function createPagingButtonCode(int $page, string $text) {
        return '<button type="button" onclick="' . $this->componentName . '_page(' . $page . ')">' . $text . '</button>';
    }

    private function getGridUrl() {
        return ['page' => $this->getGridPage(), 'action' => $this->httpRequest->query['action']];
    }

    private function getGridPage() {
        $page = 0;

        if(isset($this->httpRequest->query['gridPage'])) {
            $page = $this->httpRequest->query['gridPage'];
        }

        return (int)$page;
    }

    public function actionRefresh() {
        $this->build();
        return ['grid' => $this->render()];
    }

    private function getTotalCount() {
        $dataSource = clone $this->dataSource;

        $dataSource->limit(0)->offset(0)->select(['COUNT(*) AS cnt']);
        $result = $dataSource->execute()->fetch('cnt');
        return $result;
    }

    public function actionPage() {
        $this->build();
        return ['grid' => $this->render()];
    }
}

?>