<?php

namespace App\UI\GridBuilder;

use App\Logger\Logger;
use Exception;

/**
 * Grid builder is a component used to create data grids or tables.
 * 
 * Functions supported:
 * - adding checkboxes
 * - data reversing
 * - custom column definitions
 * - custom value parsing (using functions defined by user)
 * - row actions (info, edit, delete, etc.)
 * - pagination
 * - exporting
 * - refreshing
 * 
 * @author Lukas Velek
 * @version 1.2
 * @deprecated
 */
class GridBuilder {
    private array $actions;
    private array $columns;
    private array $dataSourceArray;
    private array $callbacks;
    private array $rowCallbacks;
    private array $belowGridElementsCode;
    private array $exportCallbacks;
    
    private ?string $headerCheckbox;
    private string $emptyDataSourceMessage;
    private string $idElement;

    private mixed $renderRowCheckbox;
    private mixed $dataSourceCallback;

    private bool $reverse;
    private bool $alwaysDrawHeaderCheckbox;
    private bool $displayNoEntriesMessage;

    private ?GridControls $gridControls;
    private ?Table $prebuiltTable;
    private IGridReducer $reducer;
    private bool $applyReducer;

    /**
     * Grid builder constructor
     */
    public function __construct(IGridReducer $gridReducer, bool $applyReducer) {
        $this->reducer = $gridReducer;
        $this->applyReducer = $applyReducer;

        $this->columns = [];
        $this->actions = [];
        $this->dataSourceArray = [];
        $this->callbacks = [];
        $this->rowCallbacks = [];
        $this->belowGridElementsCode = [];
        $this->exportCallbacks = [];

        $this->headerCheckbox = null;
        $this->renderRowCheckbox = null;
        $this->dataSourceCallback = null;
        $this->emptyDataSourceMessage = 'No data found';
        $this->idElement = 'gridbuilder-grid';

        $this->reverse = false;
        $this->alwaysDrawHeaderCheckbox = false;
        $this->displayNoEntriesMessage = true;

        $this->gridControls = null;
        $this->prebuiltTable = null;
    }

    public function getColumns() {
        return $this->columns;
    }

    public function setIdElement(string $idElement) {
        $this->idElement = $idElement;
    }

    private function addBelowGridElementCode(string $code) {
        $this->belowGridElementsCode[] = $code;
    }

    /**
     * Reverses table data (rows)
     * 
     * @param bool $reverse True if reversing is enabled or false if not
     */
    public function reverseData(bool $reverse = true) {
        $this->reverse = $reverse;
    }

    /**
     * Adds checkbox to the header of the table (column head names)
     * 
     * @param string $id HTML input element ID
     * @param string $onChange HTML input element onchange JS function
     * @param bool $drawAlways True if the header checkbox should be always drawn or false if not
     */
    public function addHeaderCheckbox(string $id, string $onChange, bool $drawAlways = false) {
        $this->headerCheckbox = '<input type="checkbox" id="' . $id . '" onchange="' . $onChange . '">';
        $this->alwaysDrawHeaderCheckbox = $drawAlways;
    }

    /**
     * Adds checkbox to each table row
     * 
     * @param callable $renderRowCheckbox Method called when drawing the checkbox
     */
    public function addRowCheckbox(callable $renderRowCheckbox) {
        $this->renderRowCheckbox = $renderRowCheckbox;
    }

    /**
     * Adds custom table cell value override. It calls the callback with parameters: Cell entity (see App\UI\GridBuilder\Cell), Table entity, value. The callback can return either the value itself or the modified Cell instance.
     * 
     * @param string $entityVarName Name of the column header
     * @param callable $func Method called when rendering
     */
    public function addOnColumnRender(string $entityVarName, callable $func) {
        $this->callbacks[$entityVarName] = $func;
    }

    /**
     * Adds custom table row override. It calls the callback with parameters: Row entity (see App\UI\GridBuilder\Row). The callback must return the modified Row instance.
     * 
     * @param string $entityPrimaryKey Primary key of the table entity
     * @param callable $func Callback method called when rendering
     */
    public function addOnRowRender(string $entityPrimaryKey, callable $func) {
        $this->rowCallbacks[$entityPrimaryKey] = $func;
    }

    /**
     * Adds custom table cell value override for exporting. It calls the callback with parameters: Table entity.
     */
    public function addOnExportRender(string $entityVarName, callable $func) {
        $this->exportCallbacks[$entityVarName] = $func;
    }

    /**
     * Adds column headers
     * 
     * @param array $columns Column names
     */
    public function addColumns(array $columns) {
        foreach($columns as $k => $v) {
            $this->columns[$k] = $v;
        }
    }

    /**
     * Adds row action
     * 
     * The internal function passes a entity object as a parameter to the function.
     * 
     * @param callable $createUrl Method used for rendering the action
     */
    public function addAction(callable $createUrl) {
        $this->actions[] = $createUrl;
    }

    /**
     * Adds data source as an array
     * 
     * @param array $objectArray Array of objects
     */
    public function addDataSource(array $objectArray) {
        $this->dataSourceArray = $objectArray;
    }

    /**
     * Adds data source as a callback
     * 
     * @param callable $dataSourceCallback Method used for obtaining table data
     */
    public function addDataSourceCallback(callable $dataSourceCallback) {
        $this->dataSourceCallback = $dataSourceCallback;
    }

    /**
     * Sets custom message that is shown if the data source (or table respectively) is empty
     * 
     * @param string $text Custom message
     */
    public function setCustomEmptyDataSourceMessage(string $text) {
        $this->emptyDataSourceMessage = $text;
    }

    /**
     * Method that builds the table and returns its HTML code
     * 
     * @return string HTML table code
     */
    public function build() {
        $table = $this->prebuild();

        $code = $table->render();

        if(!empty($this->belowGridElementsCode)) {
            foreach($this->belowGridElementsCode as $bgec) {
                $code .= $bgec;
            }
        }

        if($this->gridControls !== null) {
            $code .= $this->gridControls->render();
        }

        return $code;
    }

    /**
     * Performs grid prebuilding
     * 
     * @return GridTable GridTable instance
     */
    private function prebuild() {
        if($this->applyReducer) {
            $this->reducer->applyReducer($this);
        }

        if($this->prebuiltTable !== null) {
            return $this->prebuiltTable;
        }

        $table = new Table();

        $table->setId($this->idElement);

        // title
        $headerRow = new Row();
        $headerRow->setHeader();
        if(!is_null($this->headerCheckbox) && (is_callable($this->renderRowCheckbox) || $this->alwaysDrawHeaderCheckbox)) {
            $cell = new Cell();
            $cell->setValue($this->headerCheckbox);
            $cell->setHeader();
            $headerRow->addCell($cell);
        }
        if(!empty($this->actions)) {
            $cell = new Cell();
            $cell->setValue('Actions');
            $cell->setHeader();
            $cell->setColspan(count($this->actions));
            $headerRow->addCell($cell);
        }
        foreach($this->columns as $varName => $title) {
            $cell = new Cell();
            $cell->setValue($title);
            $cell->setHeader();
            $headerRow->addCell($cell);
        }
        $table->addRow($headerRow, true);
        // end of title

        // data
        $entityRows = [];
        if(empty($this->dataSourceArray) && (is_null($this->dataSourceCallback) || !is_callable($this->dataSourceCallback))) {
            $colspan = count($this->actions) + count($this->columns);
            
            if(!is_null($this->headerCheckbox)) {
                $colspan += 1;
            }
            
            $cell = new Cell();
            $cell->setValue($this->emptyDataSourceMessage);
            $cell->setColspan($colspan);
            $cell->setElementId('grid-empty-message');
            
            $entityRow = new Row();
            $entityRow->addCell($cell);

            if($this->displayNoEntriesMessage === TRUE) {
                $entityRows[] = $entityRow;
            }
        } else {
            if(empty($this->dataSourceArray) && is_callable($this->dataSourceCallback)) {
                $this->dataSourceArray = call_user_func($this->dataSourceCallback);
            }

            if(empty($this->dataSourceArray) || is_null($this->dataSourceArray)) {
                $colspan = count($this->actions) + count($this->columns);
            
                if(!is_null($this->headerCheckbox)) {
                    $colspan += 1;
                }
                
                $cell = new Cell();
                $cell->setValue($this->emptyDataSourceMessage);
                $cell->setColspan($colspan);
                $cell->setElementId('grid-empty-message');
                
                $entityRow = new Row();
                $entityRow->addCell($cell);

                if($this->displayNoEntriesMessage === TRUE) {
                    $entityRows[] = $entityRow;
                }
            } else {
                foreach($this->dataSourceArray as $entity) {
                    $entityRow = new Row();
    
                    if(!is_null($this->renderRowCheckbox)) {
                        $cell = new Cell();
                    
                        try {
                            $result = call_user_func($this->renderRowCheckbox, $entity);

                            $cell->setValue($result);
                        } catch(Exception $e) {
                            throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                        }

                        $entityRow->addCell($cell);
                    }
        
                    foreach($this->actions as $action) {
                        $cell = new Cell();
                        $cell->setIsForAction();
                        
                        try {
                            $result = $action($entity);

                            $cell->setValue($result);
                        } catch(Exception $e) {
                            throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                        }

                        $entityRow->addCell($cell);
                    }

                    foreach($this->columns as $varName => $title) {
                        $objectVarName = ucfirst($varName);

                        $cell = new Cell();

                        if(!$entityRow->hasPrimaryKey()) {
                            if(method_exists($entity, 'getId')) {
                                $entityRow->setPrimaryKey($entity->getId());
                                
                                if(!empty($this->rowCallbacks) && array_key_exists($entity->getId(), $this->rowCallbacks)) {
                                    try {
                                        $entityRow = $this->rowCallbacks[$entity->getId()]($entityRow);
                                    } catch(Exception $e) {
                                        throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                                    }
                                }
                            } else if(isset($entity->id)) {
                                $entityRow->setPrimaryKey($entity->id);

                                if(!empty($this->rowCallbacks) && array_key_exists($entity->id, $this->rowCallbacks)) {
                                    try {
                                        $entityRow = $this->rowCallbacks[$entity->id]($entityRow);
                                    } catch(Exception $e) {
                                        throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                                    }
                                }
                            }
                        }

                        $defaultValue = '-';
                        if(method_exists($entity, 'get' . $objectVarName)) {
                            try {
                                $result = $entity->{'get' . $objectVarName}();
                                $defaultValue = $result;

                                $cell->setValue($result);
                            } catch(Exception $e) {
                                throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                            }
                        } else if(method_exists($entity, $objectVarName)) {
                            try {
                                $result = $entity->$objectVarName();
                                $defaultValue = $result;

                                $cell->setValue($result);
                            } catch(Exception $e) {
                                throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                            }
                        } else if(isset($entity->$varName)) {
                            $cell->setValue($entity->$varName);
                            $defaultValue = $entity->$varName;
                        } else {
                            $cell->setStyle('background-color: red');
                        }

                        if(array_key_exists($varName, $this->callbacks)) {
                            $cell->resetStyle();
                            
                            try {
                                $result = $this->callbacks[$varName]($cell, $entity, $defaultValue);

                                if($result instanceof Cell) {
                                    $cell = $result;
                                } else {
                                    $cell->setValue($result);
                                }
                            } catch(Exception $e) {
                                throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                            }
                        }
    
                        $entityRow->addCell($cell);
                    }
        
                    $entityRows[] = $entityRow;
                }
            }
        }

        if($this->reverse === TRUE) {
            $tmp = [];

            for($i = (count($entityRows) - 1); $i >= 0; $i--) {
                $tmp[] = $entityRows[$i];
            }

            $entityRows = $tmp;
        }

        $table->bulkAddRows($entityRows);
        // end of data

        $this->prebuiltTable = $table;

        return $this->prebuiltTable;
    }

    /**
     * Method that creates grid paging controls. It displays all the buttons but only those that are available are not disabled. When a button is clicked a JS function (provided in the first parameter - $jsHandlerName) is called.
     * The parameters of the JS handler function are the page and the calling user ID (provided in method's last parameter - $userId).
     * 
     * @param string $jsHandlerName JS handler function
     * @param int $page Current page
     * @param int $lastPage Last page
     * @param int $userId User ID
     * @return string HTML code
     */
    private function createGridControls(string $jsHandlerName, int $page, int $lastPage, array $otherArguments = []) {
        if(!empty($otherArguments)) {
            $tmp = [];

            foreach($otherArguments as $oa) {
                if(is_numeric($oa)) {
                    $tmp[] = $oa;
                } else {
                    $tmp[] = '\'' . $oa . '\'';
                }
            }

            $otherArguments = ', ' . implode(', ', $tmp);
        } else {
            $otherArguments = '';
        }

        $firstButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

        if($page == 0) {
            $firstButton .= '0' . $otherArguments . ')" disabled>';
        } else {
            $firstButton .= '0' . $otherArguments . ')">';
        }

        $firstButton .= '&lt;&lt;</button>';

        $previousButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

        if($page == 0) {
            $previousButton .= '0' . $otherArguments . ')" disabled>';
        } else {
            $previousButton .= ($page - 1) . $otherArguments . ')">';
        }

        $previousButton .= '&lt;</button>';

        $nextButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

        if(($page + 1) >= $lastPage) {
            $nextButton .= ($lastPage - 1) . $otherArguments . ')" disabled>';
        } else {
            $nextButton .= ($page + 1) . $otherArguments . ')">';
        }

        $nextButton .= '&gt;</button>';

        $lastButton = '<button type="button" class="grid-control-button-last" onclick="' . $jsHandlerName . '(';

        if(($page + 1) >= $lastPage) {
            $lastButton .= ($lastPage - 1) . $otherArguments . ')" disabled>';
        } else {
            $lastButton .= ($lastPage - 1) . $otherArguments . ')">';
        }

        $lastButton .= '&gt;&gt;</button>';
        
        $code = $firstButton . $previousButton . $nextButton . $lastButton;

        return $code;
    }

    /**
     * Creates a information section with current page information
     * 
     * @param int $page Current page
     * @param int $lastPage Last page
     * @param int $limit Number of entries displayed in the grid
     * @param int $totalCount Total number of entities available to be displayed
     * @return string HTML code
     */
    private function addGridPagingInfo(int $page, int $lastPage, int $limit, int $totalCount) {
        $offset = ($limit * $page) + 1;
        
        if($lastPage < 1) {
            $lastPage = 1;
        }
        
        if($totalCount < $offset) {
            $offset = $totalCount;
        }
        
        $code = '<p class="post-data">Page ' . ($page + 1) . ' of ' . $lastPage . ' (' . $offset . ' - ' . $totalCount . ')</p>';

        return $code;
    }

    /**
     * Creates a grid refresh link
     * 
     * @param string $jsHandlerName Name of the JS function that will handle the refresh
     * @param array $otherArguments Other arguments that will be passed to the JS function
     * @return string HTML code
     */
    private function addGridRefresh(string $jsHandlerName, array $otherArguments = []) {
        $args = array_merge([0], $otherArguments);
        $code = '<a class="post-data-link" href="#" onclick="' . $jsHandlerName . '(\'' . implode('\', \'', $args) . '\');">Refresh &orarr;</a>';
        return $code;
    }

    /**
     * Adds a section with paging controls and paging info
     * 
     * @param int $page Current page
     * @param int $lastPage Last page
     * @param int $gridSize Number of entries displayed in grid
     * @param int $totalCount Total number of entries available for grid
     * @param string $jsHandlerName Name of the JS function that will handle changing pages
     * @param array $otherArguments Other arguments that will be passed to the JS function
     */
    public function addGridPaging(int $page, int $lastPage, int $gridSize, int $totalCount, string $jsHandlerName, array $otherArguments = []) {
        $gc = new GridControls();
        $gc->setGridPagingInfo($this->addGridPagingInfo($page, $lastPage, $gridSize, $totalCount));
        $gc->setGridRefresh($this->addGridRefresh($jsHandlerName, $otherArguments));
        $gc->setGridControls($this->createGridControls($jsHandlerName, $page, $lastPage, $otherArguments));

        $this->gridControls = $gc;
    }

    /**
     * Adds an export control for the grid that allows exporting all the entries provided
     * 
     * @param ?callback $allDataSourceArrayCallback All data callback that will return all the entries
     * @param ?Logger $logger Logger instance
     */
    public function addGridExport(callable $allDataSourceArrayCallback, string $gridName, ?Logger $logger = null) {
        $control = $this->createGridExportControl($logger, $gridName, $allDataSourceArrayCallback);

        if($control === null) {
            return;
        }

        if($this->gridControls !== null) {
            $this->gridControls->setGridExport($control);
        } else {
            $gc = new GridControls();
            $gc->setGridExport($control);
            
            $this->gridControls = $gc;
        }
    }

    /**
     * Creates a export link for the grid
     * 
     * @param ?Logger $logger Logger instance
     * @param ?callback $customDataCallback Custom data callback used
     * @return string HTML code
     */
    private function createGridExportControl(?Logger $logger, string $gridName, ?callable $customDataCallback = null) {
        if(empty($this->dataSourceArray)) {
            return null;
        }

        $geh = new GridExportHandler($logger);
        $geh->setData($this);

        if($customDataCallback !== null) {
            $dataAll = $customDataCallback();
            $geh->setDataAll($dataAll);
        }

        $geh->saveCache();
        $hash = $geh->getHash();

        return '<a class="post-data-link" onclick="exportGrid(\'' . $hash . '\', \'' . $gridName . '\')" style="cursor: pointer">Export</a>';
    }

    /**
     * Returns data source array
     * 
     * @return array Data source
     */
    public function getDataSourceArray() {
        return $this->dataSourceArray;
    }

    /**
     * Returns cell callbacks
     * 
     * @return array Cell callbacks
     */
    public function getColumnCallbacks() {
        return $this->callbacks;
    }

    /**
     * Returns export cell callbacks
     * 
     * @return array Export cell callbacks
     */
    public function getExportCallbacks() {
        return $this->exportCallbacks;
    }
}

?>