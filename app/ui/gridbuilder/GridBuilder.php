<?php

namespace App\UI\GridBuilder;

use App\Logger\Logger;
use App\UI\LinkBuilder;
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
 * 
 * @author Lukas Velek
 * @version 1.2
 */
class GridBuilder {
    private array $actions;
    private array $columns;
    private array $dataSourceArray;
    private array $callbacks;
    private array $rowCallbacks;
    private array $belowGridElementsCode;
    
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

    /**
     * Grid builder constructor
     */
    public function __construct() {
        $this->columns = [];
        $this->actions = [];
        $this->dataSourceArray = [];
        $this->callbacks = [];
        $this->rowCallbacks = [];
        $this->belowGridElementsCode = [];

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
     * Adds custom table cell value override. It calls the callback with parameters: Cell entity (see App\UI\GridBuilder\Cell), Table entity. The callback can return either the value itself or the modified Cell instance.
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
        // end of data

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
    public function prebuild() {
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
            if(empty($this->dataSourceArray)) {
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

                        if(array_key_exists($varName, $this->callbacks)) {
                            try {
                                $result = $this->callbacks[$varName]($cell, $entity);

                                if($result instanceof Cell) {
                                    $cell = $result;
                                } else {
                                    $cell->setValue($result);
                                }
                            } catch(Exception $e) {
                                throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                            }
                        } else {
                            if(method_exists($entity, 'get' . $objectVarName)) {
                                try {
                                    $result = $entity->{'get' . $objectVarName}();

                                    $cell->setValue($result);
                                } catch(Exception $e) {
                                    throw new GridBuilderCustomMethodException($e->getMessage(), $e);
                                }
                            } else if(isset($entity->$varName)) {
                                $cell->setValue($entity->$varName);
                            } else {
                                $cell->setStyle('background-color: red');
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

    private function addGridRefresh(string $jsHandlerName, array $otherArguments = []) {
        $args = array_merge([0], $otherArguments);
        $code = '<a class="post-data-link" href="#" onclick="' . $jsHandlerName . '(\'' . implode('\', \'', $args) . '\');">Refresh</a>';
        return $code;
    }

    public function addGridPaging(int $page, int $lastPage, int $gridSize, int $totalCount, string $jsHandlerName, array $otherArguments = []) {
        $gc = new GridControls();
        $gc->setGridPagingInfo($this->addGridPagingInfo($page, $lastPage, $gridSize, $totalCount));
        $gc->setGridRefresh($this->addGridRefresh($jsHandlerName, $otherArguments));
        $gc->setGridControls($this->createGridControls($jsHandlerName, $page, $lastPage, $otherArguments));

        $this->gridControls = $gc;
    }

    public function addGridExport(Logger $logger) {
        $control = $this->createGridExportControl($logger);

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

    private function createGridExportControl(Logger $logger) {
        if(empty($this->dataSourceArray)) {
            return null;
        }

        $geh = new GridExportHandler($logger);
        $geh->setData($this);
        $geh->saveCache();
        $hash = $geh->getHash();

        return '<a class="post-data-link" onclick="exportGrid(\'' . $hash . '\')" style="cursor: pointer">Export</a>';
    }

    public function getDataSourceArray() {
        return $this->dataSourceArray;
    }

    public function getColumnCallbacks() {
        return $this->callbacks;
    }
}

?>