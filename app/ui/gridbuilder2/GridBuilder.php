<?php

namespace App\UI\GridBuilder2;

use App\Constants\AConstant;
use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\GridExportException;
use App\Helpers\ArrayHelper;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\AComponent;
use App\UI\HTML\HTML;
use Error;
use Exception;
use QueryBuilder\QueryBuilder;

/**
 * Grid builder is a component used to create data grids or tables.
 * 
 * Functions supported:
 * - custom column order definition
 * - custom column value override
 * - automatic value override (users, datetime, etc.)
 * - row actions (info, edit, delete, etc.)
 * - pagination
 * - refreshing
 * 
 * @author Lukas Velek
 * @version 2.0
 */
class GridBuilder extends AComponent {
    private const COL_TYPE_TEXT = 'text';
    private const COL_TYPE_DATETIME = 'datetime';
    private const COL_TYPE_BOOLEAN = 'boolean';
    private const COL_TYPE_USER = 'user';

    private ?QueryBuilder $dataSource;
    private string $primaryKeyColName;
    /**
     * @var array<string, Column> $columns
     */
    private array $columns;
    private array $columnLabels;
    private Table $table;
    private bool $enablePagination;
    private bool $enableExport;
    private int $gridPage;
    private ?int $totalCount;
    private GridHelper $helper;
    private string $gridName;

    /**
     * Methods called with parameters: DatabaseRow $row, Row $_row, HTML $rowHtml
     * @var array<callback> $onRowRender
     */
    public array $onRowRender;

    /**
     * @var array<Action> $actions
     */
    private array $actions;

    /**
     * @var array<Filter> $filters
     */
    private array $filters;
    private array $activeFilters;

    /**
     * Class constructor
     * 
     * @param HttpRequest $request HttpRequest instance
     * @param array $cfg Application configuration
     */
    public function __construct(HttpRequest $request, array $cfg) {
        parent::__construct($request, $cfg);

        $this->dataSource = null;
        $this->columns = [];
        $this->columnLabels = [];
        $this->enablePagination = true;
        $this->enableExport = false;
        $this->gridPage = 0;
        $this->onRowRender = [];
        $this->actions = [];
        $this->totalCount = null;
        $this->filters = [];
        $this->activeFilters = [];
        $this->gridName = 'MyGrid';
    }

    /**
     * Starts up the component
     */
    public function startup() {
        parent::startup();

        $this->gridPage = $this->getGridPage();
    }

    /**
     * Sets the GridHelper instance
     * 
     * @param GridHelper $helper GridHelper instance
     */
    public function setHelper(GridHelper $helper) {
        $this->helper = $helper;
    }

    public function setGridName(string $name) {
        $this->gridName = $name;
    }

    /**
     * Enables pagination
     */
    public function enablePagination() {
        $this->enablePagination = true;
    }

    /**
     * Disables pagination
     */
    public function disablePagination() {
        $this->enablePagination = false;
    }

    public function enableExport() {
        $this->enableExport = true;
    }

    public function disableExport() {
        $this->enableExport = false;
    }

    public function addFilter(string $key, mixed $value, array $options) {
        $filter = new Filter($key, $value, $options);
        $this->filters[$key] = &$filter;

        return $filter;
    }

    public function addAction(string $name) {
        $action = new Action($name);
        $this->actions[$name] = &$action;

        return $action;
    }

    public function addColumnEnum(string $name, ?string $label = null, string $enumClass) {
        $col = $this->addColumn($name, self::COL_TYPE_TEXT, $label);
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) use ($enumClass) {
            $result = null;
            try {
                if(class_exists($enumClass)) {
                    if(in_array(AConstant::class, class_parents($enumClass))) {
                        $result = $enumClass::toString($value);
                    }
                }
            } catch(Exception $e) {}

            return $result;
        };
        $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) use ($enumClass) {
            $result = null;
            try {
                if(class_exists($enumClass)) {
                    if(in_array(AConstant::class, class_parents($enumClass))) {
                        $result = $enumClass::toString($value);
                    }
                }
            } catch(Exception $e) {}

            return $result;
        };

        return $col;
    }

    public function addColumnUser(string $name, ?string $label = null) {
        return $this->addColumn($name, self::COL_TYPE_USER, $label);
    }

    public function addColumnBoolean(string $name, ?string $label = null) {
        return $this->addColumn($name, self::COL_TYPE_BOOLEAN, $label);
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
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value === null) {
                    return '-';
                }

                $html->title(DateTimeFormatHelper::formatDateToUserFriendly($value, DateTimeFormatHelper::ATOM_FORMAT));
                return DateTimeFormatHelper::formatDateToUserFriendly($value);
            };

            $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
                return DateTimeFormatHelper::formatDateToUserFriendly($value);
            };
        } else if($type == self::COL_TYPE_USER) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value === null) {
                    return $value;
                }
                $user = $this->app->userManager->getUserById($value);
                if($user === null) {
                    return $value;
                } else {
                    return UserEntity::createUserProfileLink($user, false, 'grid-link');
                }
            };

            $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
                $user = $this->app->userManager->getUserById($value);
                if($user === null) {
                    return $value;
                } else {
                    return UserEntity::createUserProfileLink($user, false, 'grid-link');
                }
            };
        } else if($type == self::COL_TYPE_BOOLEAN) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value === true) {
                    $el = HTML::el('span')
                            ->style('color', 'green')
                            ->text('&check;');
                    $cell->setContent($el);
                } else {
                    $el = HTML::el('span')
                            ->style('color', 'red')
                            ->text('&times;');
                    $cell->setContent($el);
                }

                return $cell;
            };

            $col->onExportColumn[] = function(DatabaseRow $row, mixed $value) {
                if($value === true) {
                    return 'True';
                } else {
                    return 'False';
                }
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

        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                if($value == 'null') {
                    continue;
                }
                if(!empty($this->filters[$name]->onSqlExecute)) {
                    foreach($this->filters[$name]->onSqlExecute as $sql) {
                        $sql($qb, $this->filters[$name]);
                    }
                } else {
                    $qb->andWhere($name . ' = ?', [$value]);
                }
            }
        }

        return $qb;
    }

    public function render() {
        $this->build();

        $template = $this->getTemplate(__DIR__ . '/grid.html');

        $template->grid = $this->table->output();
        $template->controls = $this->createGridControls();
        $template->filter_modal = '';
        $template->filters = $this->createGridFilterControls();

        return $template->render()->getRenderedContent();
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

        $_tableRows['header'] = $_headerRow;

        $hasActionsCol = false;

        while($row = $cursor->fetchAssoc()) {
            $row = $this->createDatabaseRow($row);
            $rowId = $row->{$this->primaryKeyColName};

            $_row = new Row();
            $_row->setPrimaryKey($rowId);

            foreach($this->columns as $name => $col) {
                if(in_array($name, $row->getKeys())) {
                    $_cell = new Cell();
                    $_cell->setName($name);

                    $content = $row->$name;

                    if(!empty($this->columns[$name]->onRenderColumn)) {
                        foreach($this->columns[$name]->onRenderColumn as $render) {
                            try {
                                $content = $render($row, $_row, $_cell, $_cell->html, $content);
                            } catch(Exception $e) {}
                        }
                    }

                    if($content === null) {
                        $content = '-';

                        $_cell->setContent($content);
                    } else {
                        if($content instanceof Cell) {
                            $_cell = $content;
                        } else {
                            $_cell->setContent($content);
                        }
                    }

                    $_row->addCell($_cell);
                } else {
                    $_cell = new Cell();
                    $_cell->setName($name);

                    $content = '-';

                    if(!empty($this->columns[$name]->onRenderColumn)) {
                        foreach($this->columns[$name]->onRenderColumn as $render) {
                            try {
                                $content = $render($row, $_row, $_cell, $_cell->html, $content);
                            } catch(Exception $e) {}
                        }
                    }

                    if($content === null) {
                        $content = '-';
                    } else {
                        if($content instanceof Cell) {
                            $_cell = $content;
                        } else {
                            $_cell->setContent($content);
                        }
                    }

                    $_row->addCell($_cell);
                }
            }

            if(!empty($this->onRowRender)) {
                foreach($this->onRowRender as $render) {
                    try {
                        $render($row, $_row, $_row->html);
                    } catch(Exception $e) {}
                }
            }

            if(!empty($this->actions)) {
                $isAtLeastOneDisplayed = false;
                
                $canRender = [];
                foreach($this->actions as $actionName => $action) {
                    $cAction = clone $action;

                    foreach($cAction->onCanRender as $render) {
                        try {
                            $result = $render($row, $_row);

                            if($result === true) {
                                $canRender[$actionName] = $cAction;
                            } else {
                                $canRender[$actionName] = null;
                            }
                        } catch(Exception $e) {
                            $canRender[$actionName] = null;
                        }
                    }
                }

                $isAtLeastOneDisplayed = !empty($canRender);

                foreach($canRender as $name => $action) {
                    if($action instanceof Action) {
                        $cAction = clone $action;
                        $cAction->inject($row, $_row, $rowId);
                        $_cell = new Cell();
                        $_cell->setName($name);
                        $_cell->setContent($cAction->output()->toString());
                        $_cell->setClass('grid-cell-action');
                    } else {
                        $_cell = new Cell();
                        $_cell->setName($name);
                        $_cell->setContent('-');
                        $_cell->setClass('grid-cell-action');
                    }

                    $_row->addCell($_cell, true);
                }

                if($isAtLeastOneDisplayed && !$hasActionsCol) {
                    $_headerCell = new Cell();
                    $_headerCell->setName('actions');
                    $_headerCell->setContent('Actions');
                    $_headerCell->setHeader();
                    $_headerCell->setSpan(count($canRender));
                    $_tableRows['header']->addCell($_headerCell, true);
                    $hasActionsCol = true;
                }
            }

            $_tableRows[] = $_row;
        }

        if(count($_tableRows) == 1) {
            $cell = new Cell();
            $cell->setSpan(count($this->columns));
            $cell->setName('no-data-message');
            $cell->setContent('No data found.');

            $row = new Row();
            $row->addCell($cell);
            $row->setPrimaryKey(null);

            $_tableRows[] = $row;
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
                <div class="col-md-3">
                    ' . $this->createGridPagingControl() . '
                </div>
                
                <div class="col-md">
                    ' . $this->createGridPageInfo() . '
                </div>
                
                <div class="col-md" ' . ($this->enableExport ? '' : ' id="right"') . '>
                    ' . $this->createGridRefreshControl() . '
                </div>

                ' . ($this->enableExport ? ('<div class="col-md" id="right">' . $this->createGridExportControl() . '</div>') : ('')) . '
            </div>

            <span>
                ' . $this->createScripts() . '
            </span>
        ';

        return $code;
    }

    private function createScripts() {
        $scripts = [];

        $addScript = function(AjaxRequestBuilder $arb) use (&$scripts) {
            $scripts[] = '<script type="text/javascript">' . $arb->build() . '</script>';
        };

        // REFRESH
        $refreshHeader = ['gridPage' => '_page'];
        $refreshArgs = ['_page'];
        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                $pName = '_' . $name;

                $refreshHeader[$name] = $pName;
                $refreshArgs[] = $pName;
            }
        }

        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setComponentAction($this->presenter, $this->componentName . '-refresh')
            ->setHeader($refreshHeader)
            ->setFunctionName($this->componentName . '_gridRefresh')
            ->setFunctionArguments($refreshArgs)
            ->updateHTMLElement('grid', 'grid')
            ->setComponent()
        ;

        $addScript($arb);

        // PAGINATION
        $paginationHeader = ['gridPage' => '_page'];
        $paginationArgs = ['_page'];
        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                $pName = '_' . $name;

                $paginationHeader[$name] = $pName;
                $paginationArgs[] = $pName;
            }
        }

        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setComponentAction($this->presenter, $this->componentName . '-page')
            ->setHeader($paginationHeader)
            ->setFunctionName($this->componentName . '_page')
            ->setFunctionArguments($paginationArgs)
            ->updateHTMLElement('grid', 'grid')
            ->setComponent()
        ;

        $addScript($arb);

        // FILTER
        if(!empty($this->filters)) {
            $arb = new AjaxRequestBuilder();

            $headerParams = [];
            $fArgs = [];
            foreach($this->filters as $name => $filter) {
                $hName = '_' . $name;
                $headerParams[$name] = $hName;
                $fArgs[] = $hName;
            }

            $arb->setMethod()
                ->setComponentAction($this->presenter, $this->componentName . '-filter')
                ->setHeader($headerParams)
                ->setFunctionName($this->componentName . '_filter')
                ->setFunctionArguments($fArgs)
                ->setComponent()
                ->updateHTMLElement('grid', 'grid')
            ;

            $addScript($arb);
        }

        // FILTER MODAL
        if(!empty($this->filters)) {
            $scripts[] = '
                <script type="text/javascript">
                    async function ' . $this->componentName . '_processFilterModalOpen() {
                        $("#grid-filter-modal-inner")
                            .css("height", "90%")
                            .css("visibility", "visible")
                            .css("width", "90%");
                    }
                </script>
            ';

            $scripts[] = '
                <script type="text/javascript">
                    function ' . $this->componentName . '_processFilterClear() {
                        location.href = "' . $this->presenter->createURLString($this->presenter->getAction()) . '";
                    }
                </script>
            ';
        }

        // EXPORT MODAL
        if($this->enableExport) {
            $scripts[] = '
                <script type="text/javascript">
                    async function ' . $this->componentName . '_processExportModalOpen() {
                        $("#grid-export-modal-inner")
                            .css("height", "90%")
                            .css("visibility", "visible")
                            .css("width", "90%");
                    }
                </script>
            ';
        }

        // EXPORT
        if($this->enableExport) {
            $arb = new AjaxRequestBuilder();

            $headerParams = [];
            $fArgs = [];
            foreach($this->filters as $name => $filter) {
                $hName = '_' . $name;
                $headerParams[$name] = $hName;
                $fArgs[] = $hName;
            }

            $arb->setMethod()
                ->setComponentAction($this->presenter, $this->componentName . '-exportLimited')
                ->setHeader($headerParams)
                ->setFunctionName($this->componentName . '_exportLimited')
                ->setFunctionArguments($fArgs)
                ->addWhenDoneOperation('if(obj.file) {
                    window.open(obj.file, "_blank");
                }')
            ;

            $addScript($arb);

            $arb->setMethod()
                ->setComponentAction($this->presenter, $this->componentName . '-exportUnlimited')
                ->setHeader($headerParams)
                ->setFunctionName($this->componentName . '_exportUnlimited')
                ->setFunctionArguments($fArgs)
                ->addWhenDoneOperation('if(obj.success) { alert("Your export will be created asynchronously. You can find it in Grid export management section."); }')
            ;

            $addScript($arb);
        }

        return implode('', $scripts);
    }

    private function createGridExportControl() {
        return '<button type="button" onclick="' . $this->componentName . '_processExportModalOpen()">Export</button>';
    }

    private function createGridPageInfo() {
        $totalCount = $this->getTotalCount();
        $lastPage = (int)ceil($totalCount / $this->cfg['GRID_SIZE']);

        $lastPageCount = $this->cfg['GRID_SIZE'] * ($this->gridPage + 1);
        if($lastPageCount > $totalCount) {
            $lastPageCount = $totalCount;
        }

        return 'Page ' . ($this->gridPage + 1) . ' of ' . $lastPage . ' (' . ($this->cfg['GRID_SIZE'] * $this->gridPage) . ' - ' . $lastPageCount . ')';
    }

    private function createGridRefreshControl() {
        $args = [$this->getGridPage()];

        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                $args[] = '\'' . $value . '\'';
            }
        }

        return '<a class="post-data-link" href="#" onclick="' . $this->componentName . '_gridRefresh(' . implode(', ', $args) . ')">Refresh &orarr;</a>';
    }

    private function createGridPagingControl() {
        $totalCount = $this->getTotalCount();
        $lastPage = (int)ceil($totalCount / $this->cfg['GRID_SIZE']) - 1;

        $firstPageBtn = $this->createPagingButtonCode(0, '&lt;&lt;', ($this->gridPage == 0));
        $previousPageBtn = $this->createPagingButtonCode(($this->gridPage - 1), '&lt;', ($this->gridPage == 0));
        $nextPageBtn = $this->createPagingButtonCode(($this->gridPage + 1), '&gt;', ($this->gridPage == $lastPage));
        $lastPageBtn = $this->createPagingButtonCode($lastPage, '&gt;&gt;', ($this->gridPage == $lastPage));

        return implode('', [$firstPageBtn, $previousPageBtn, $nextPageBtn, $lastPageBtn]);
    }

    /**
     * Creates a paging button code
     * 
     * @param int $page Page to be changed to
     * @param string $text Button text
     * @param bool $disabled True if button is disabled or false if not
     * @return string HTML code
     */
    private function createPagingButtonCode(int $page, string $text, bool $disabled = false) {
        $args = [$page];
        if(!empty($this->activeFilters)) {
            foreach($this->activeFilters as $name => $value) {
                $args[] = '\'' . $value . '\'';
            }
        }

        return '<button type="button" class="grid-control-button" onclick="' . $this->componentName . '_page(' . implode(', ', $args) . ')"' . ($disabled ? ' disabled' : '') . '>' . $text . '</button>';
    }

    /**
     * Returns current grid page
     * 
     * @return int Current grid page
     */
    private function getGridPage() {
        $page = 0;

        if(isset($this->httpRequest->query['gridPage'])) {
            $page = $this->httpRequest->query['gridPage'];
        }

        $page = $this->helper->getGridPage($this->gridName, $page);

        return (int)$page;
    }

    /**
     * Returns total entry count
     * 
     * @return int Total entry count
     */
    private function getTotalCount() {
        if($this->totalCount !== null) {
            return $this->totalCount;
        }

        $dataSource = clone $this->dataSource;

        $dataSource->resetLimit()->resetOffset()->select(['COUNT(*) AS cnt']);
        $this->totalCount = $dataSource->execute()->fetch('cnt');
        return $this->totalCount;
    }

    /**
     * Creates code for filter controls
     * 
     * @return string HTML code
     */
    private function createGridFilterControls() {
        if(empty($this->filters)) {
            return '';
        }

        $el = HTML::el('span');

        $btn = HTML::el('button')
                ->addAtribute('type', 'button')
                ->onClick($this->componentName . '_processFilterModalOpen()')
                ->text('Filter')
        ;

        $btns = [
            $btn->toString()
        ];

        if(!empty($this->activeFilters)) {
            $btn = HTML::el('button')
                    ->addAtribute('type', 'button')
                    ->onClick($this->componentName . '_processFilterClear()')
                    ->text('Clear filter')
            ;

            $btns[] = $btn->toString();
        }

        $el->text(implode('', $btns));

        return $el->toString();
    }

    // GRID AJAX REQUEST HANDLERS

    /**
     * Refreshes the grid
     * 
     * @return array<string, string> Response
     */
    public function actionRefresh() {
        foreach($this->filters as $name => $filter) {
            if(isset($this->httpRequest->query[$name])) {
                $this->activeFilters[$name] = $this->httpRequest->query[$name];
            }
        }

        $this->build();
        return ['grid' => $this->render()];
    }

    /**
     * Changes the grid page
     * 
     * @return array<string, string> Response
     */
    public function actionPage() {
        foreach($this->filters as $name => $filter) {
            if(isset($this->httpRequest->query[$name])) {
                $this->activeFilters[$name] = $this->httpRequest->query[$name];
            }
        }

        $this->build();
        return ['grid' => $this->render()];
    }

    public function actionFilter() {
        foreach($this->filters as $name => $filter) {
            if(isset($this->httpRequest->query[$name])) {
                $this->activeFilters[$name] = $this->httpRequest->query[$name];
            }
        }

        $this->build();
        return ['grid' => $this->render()];
    }

    public function actionExportLimited() {
        $ds = clone $this->dataSource;
        $ds = $this->processQueryBuilderDataSource($ds);

        $result = [];
        try {
            $geh = $this->createGridExportHandler($ds);
            [$file, $hash] = $geh->exportNow();
            $result = ['file' => $file, 'hash' => $hash];
        } catch(AException $e) {
            throw new GridExportException('Could not process limited export.', $e);
        }

        return $result;
    }

    public function actionExportUnlimited() {
        $ds = clone $this->dataSource;
        $ds = $this->processQueryBuilderDataSource($ds);
        
        $result = [];
        try {
            $geh = $this->createGridExportHandler($ds);
            $hash = $geh->exportAsync();
            $result = ['hash' => $hash];
        } catch(AException|Exception $e) {
            throw new GridExportException('Could not process unlimited export.', $e);
        }

        return $result;
    }

    // FILTER MODAL COMPONENT
    protected function createComponentFilter() {
        $filter = GridFilter::createFromComponent($this);
        $filter->setFilters($this->filters);
        $filter->setGridComponentName($this->componentName);
        $filter->setGridColumns($this->columnLabels);
        $filter->setActiveFilters($this->activeFilters);

        return $filter;
    }

    // EXPORT MODAL COMPONENT
    protected function createComponentExport() {
        $gem = new GridExportModal($this);

        $ds = clone $this->dataSource;
        $ds = $this->processQueryBuilderDataSource($ds);
        $gem->setDataSource($ds);

        return $gem;
    }

    /**
     * Creates an instance of component from other component
     * 
     * @param AComponent $component Other component
     * @return AComponent
     */
    public static function createFromComponent(AComponent $component) {
        $obj = new self($component->httpRequest, $component->cfg);
        $obj->setApplication($component->app);
        $obj->setPresenter($component->presenter);

        return $obj;
    }

    private function createGridExportHandler(QueryBuilder $dataSource) {
        return new GridExportHandler(
            $dataSource,
            $this->primaryKeyColName,
            $this->columns,
            $this->columnLabels,
            $this->presenter->getUserId(),
            $this->cfg,
            $this->app,
            $this->gridName
        );
    }
}

?>