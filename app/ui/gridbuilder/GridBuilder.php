<?php

namespace App\UI\GridBuilder;

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
 * @version 1.1
 */
class GridBuilder {
    private array $actions;
    private array $columns;
    private array $dataSourceArray;
    private array $callbacks;

    private int $tableBorder;
    
    private ?string $headerCheckbox;
    private string $emptyDataSourceMessage;

    private mixed $renderRowCheckbox;
    private mixed $dataSourceCallback;

    private bool $reverse;
    private bool $alwaysDrawHeaderCheckbox;
    private bool $displayNoEntriesMessage;

    private array $belowGridElementsCode;

    /**
     * Grid builder constructor
     */
    public function __construct() {
        $this->columns = [];
        $this->actions = [];
        $this->dataSourceArray = [];
        $this->callbacks = [];

        $this->tableBorder = 1;

        $this->headerCheckbox = null;
        $this->renderRowCheckbox = null;
        $this->dataSourceCallback = null;
        $this->emptyDataSourceMessage = 'No data found';
        $this->reverse = false;
        $this->alwaysDrawHeaderCheckbox = false;
        $this->displayNoEntriesMessage = true;

        $this->belowGridElementsCode = [];
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
     * Adds custom table element render
     * 
     * @param string $entityVarName Name of the column header
     * @param callable $func Method called when rendering
     */
    public function addOnColumnRender(string $entityVarName, callable $func) {
        $this->callbacks[$entityVarName] = $func;
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
     * Sets custom table border
     * 
     * @param int $border Table border
     */
    public function setTableBorder(int $border) {
        $this->tableBorder = $border;
    }

    /**
     * Method that builds the table and returns its HTML code
     * 
     * @return string HTML table code
     */
    public function build() {
        $code = '<div class="row"><table border="' . $this->tableBorder . '" id="tablebuilder-table">';

        // title
        $headerRow = new Row();
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
        $code .= $headerRow->render();
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
                    //entityRow = '<tr>';
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

                        if(array_key_exists($varName, $this->callbacks)) {
                            try {
                                $result = $this->callbacks[$varName]($entity);

                                $cell->setValue($result);
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
    
                        //$entityRow .= $cell->render();
                        $entityRow->addCell($cell);
                    }
        
                    //$entityRow .= '</tr>';
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

        foreach($entityRows as $entityRow) {
            $code .= $entityRow->render();
        }
        // end of data

        $code .= '</table></div>';

        if(!empty($this->belowGridElementsCode)) {
            foreach($this->belowGridElementsCode as $bgec) {
                $code .= $bgec;
            }
        }

        return $code;
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
            $nextButton .= $lastPage . $otherArguments . ')" disabled>';
        } else {
            $nextButton .= ($page + 1) . $otherArguments . ')">';
        }

        $nextButton .= '&gt;</button>';

        $lastButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

        if(($page + 1) >= $lastPage) {
            $lastButton .= $lastPage . $otherArguments . ')" disabled>';
        } else {
            $lastButton .= $lastPage . $otherArguments . ')">';
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
        $code = '<div class="row">';
        $code .= '<div class="col-md"><div class="row"><div class="col-md-4">' . $this->addGridPagingInfo($page, $lastPage, $gridSize, $totalCount) . '</div><div class="col-md">' . $this->addGridRefresh($jsHandlerName, $otherArguments) . '</div></div></div>';
        $code .= '<div class="col-md" id="right">' . $this->createGridControls($jsHandlerName, $page, $lastPage, $otherArguments) . '</div>';
        $code .= '</div>';

        $this->addBelowGridElementCode($code);
    }
}

?>