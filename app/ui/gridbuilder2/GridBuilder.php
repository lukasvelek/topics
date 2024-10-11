<?php

namespace App\UI\GridBuilder2;

use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\FileManager;
use App\Core\Http\HttpRequest;
use App\Entities\UserEntity;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\Modules\TemplateObject;
use App\UI\AComponent;
use App\UI\HTML\HTML;
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
    private int $gridPage;
    private ?int $totalCount;
    private GridHelper $helper;

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
        $this->gridPage = 0;
        $this->onRowRender = [];
        $this->actions = [];
        $this->totalCount = null;
        $this->filters = [];
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

            $col->onExportColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, mixed $value) {
                return DateTimeFormatHelper::formatDateToUserFriendly($value);
            };
        } else if($type == self::COL_TYPE_USER) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                $user = $this->app->userManager->getUserById($value);
                if($user === null) {
                    return $value;
                } else {
                    return UserEntity::createUserProfileLink($user, false, 'grid-link');
                }
            };
        } else if($type == self::COL_TYPE_BOOLEAN) {
            $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
                if($value == true) {
                    $el = HTML::el('span')
                            ->style('color', 'green')
                            ->text(/*'Yes'*/ '&check;');
                    $cell->setContent($el);
                } else {
                    $el = HTML::el('span')
                            ->style('color', 'red')
                            ->text(/*'No'*/ '&times;');
                    $cell->setContent($el);
                }

                return $cell;
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

        if(!empty($this->filters)) {
            foreach($this->filters as $name => $filter) {
                if(!empty($filter->onSqlExecute)) {
                    foreach($filter->onSqlExecute as $sql) {
                        $sql($qb, $filter);
                    }
                }
            }
        }

        return $qb;
    }

    public function render() {
        $this->build();

        $template = $this->getTemplate();

        $template->filters = $this->createGridFilterControls();
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

        $_tableRows['header'] = $_headerRow;

        $hasActionsCol = false;

        while($row = $cursor->fetchAssoc()) {
            $row = $this->createDatabaseRow($row);
            $_row = new Row();
            $_row->setPrimaryKey($row->{$this->primaryKeyColName});

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
                    foreach($action->onCanRender as $render) {
                        try {
                            $result = $render($row, $_row);

                            if($result === true) {
                                $canRender[$actionName] = $action;
                            }
                        } catch(Exception $e) {}
                    }
                }

                $isAtLeastOneDisplayed = !empty($canRender);

                foreach($canRender as $name => $action) {
                    if($action instanceof Action) {
                        $action->inject($row, $_row, $row->{$this->primaryKeyColName});
                        $_cell = new Cell();
                        $_cell->setName($name);
                        $_cell->setContent($action->output());
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
                    ' . $this->createGridRefreshControl() . '
                </div>

                <div class="col-md" id="right">
                    ' . $this->createGridPageInfo() . '
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

        $addScript = function(AjaxRequestBuilder $arb) use (&$scripts) {
            $scripts[] = '<script type="text/javascript">' . $arb->build() . '</script>';
        };

        // REFRESH
        $refreshHeader = ['gridPage' => '_page'];
        $refreshArgs = ['_page'];
        if(isset($this->httpRequest->query['filterKey'])) {
            $refreshHeader['filterKey'] = '_filterKey';
            $refreshArgs[] = '_filterKey';
        }
        if(isset($this->httpRequest->query['filterType'])) {
            $refreshHeader['filterType'] = '_filterType';
            $refreshArgs[] = '_filterType';
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
        if(isset($this->httpRequest->query['filterKey'])) {
            $paginationHeader['filterKey'] = '_filterKey';
            $paginationArgs[] = '_filterKey';
        }
        if(isset($this->httpRequest->query['filterType'])) {
            $paginationHeader['filterType'] = '_filterType';
            $paginationArgs[] = '_filterType';
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
            $arb->setMethod()
                ->setComponentAction($this->presenter, $this->componentName, '-filter')
                ->setHeader(['filterKey' => '_filterKey', 'filterType' => '_filterType'])
                ->setFunctionName($this->componentName . '_filter')
                ->setFunctionArguments(['_filterType', '_filterKey'])
                ->updateHTMLElement('grid', 'grid')
                ->setComponent()
            ;

            $addScript($arb);

            $scripts[] = '
                <script type="text/javascript">
                    async function processGridFilterSubmit(_gridPage) {
                        
                    }
                </script>
            ';
        }

        return implode('', $scripts);
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
        return '<a class="post-data-link" href="#" onclick="' . $this->componentName . '_gridRefresh(' . $this->getGridPage() . ')">Refresh &orarr;</a>';
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
        $filterKey = '';
        if(isset($this->httpRequest->query['filterKey'])) {
            $filterKey .= ', \'' . $this->httpRequest->query['filterKey'] . '\'';
        }

        $filterType = '';
        if(isset($this->httpRequest->query['filterType'])) {
            $filterType .= ', \'' . $this->httpRequest->query['filterType'] . '\'';
        }

        return '<button type="button" class="grid-control-button" onclick="' . $this->componentName . '_page(' . $page . $filterKey . $filterType . ')"' . ($disabled ? ' disabled' : '') . '>' . $text . '</button>';
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

        $page = $this->helper->getGridPage($this->componentName, $page);

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

        $code = '';
        foreach($this->filters as $name => $filter) {
            $filter->inject($this->componentName);
            $code .= $filter->output()->toString();
        }

        $submit = HTML::el('button')
                    ->addAtribute('type', 'button')
                    ->onClick('processGridFilterSubmit(' . $this->gridPage . ')')
                    ->href('#')
                    ->text('Submit');

        $code .= $submit->toString();

        return $code;
    }

    // GRID AJAX REQUEST HANDLERS

    /**
     * Refreshes the grid
     * 
     * @return array<string, string> Response
     */
    public function actionRefresh() {
        $this->build();
        return ['grid' => $this->render()];
    }

    /**
     * Changes the grid page
     * 
     * @return array<string, string> Response
     */
    public function actionPage() {
        $this->build();
        return ['grid' => $this->render()];
    }
}

?>