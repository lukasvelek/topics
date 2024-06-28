<?php

namespace App\UI\GridBuilder;

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
        $code = '<table border="' . $this->tableBorder . '" id="tablebuilder-table">';

        // title
        $headerRow = '<tr>';
        if(!is_null($this->headerCheckbox) && (is_callable($this->renderRowCheckbox) || $this->alwaysDrawHeaderCheckbox)) {
            $headerRow .= '<th>' . $this->headerCheckbox . '</th>';
        }
        if(!empty($this->actions)) {
            $headerRow .= '<th';

            if(count($this->actions) > 1) {
                $headerRow .= ' colspan="' . count($this->actions) . '"';
            }

            $headerRow .= '>';

            $headerRow .= 'Actions</th>';
        }
        foreach($this->columns as $varName => $title) {
            $headerRow .= '<th>' . $title . '</th>';
        }
        $headerRow .= '</tr>';
        $code .= $headerRow;
        // end of title

        // data
        $entityRows = [];
        if(empty($this->dataSourceArray) && (is_null($this->dataSourceCallback) || !is_callable($this->dataSourceCallback))) {
            $entityRow = '<tr><td';

            $colspan = count($this->actions) + count($this->columns);

            if(!is_null($this->headerCheckbox)) {
                $colspan += 1;
            }

            $entityRow .= ' colspan="' . $colspan . '" id="grid-empty-message">' . $this->emptyDataSourceMessage . '</td></tr>';

            if($this->displayNoEntriesMessage === TRUE) {
                $entityRows[] = $entityRow;
            }
        } else {
            if(empty($this->dataSourceArray)) {
                $this->dataSourceArray = call_user_func($this->dataSourceCallback);
            }

            if(empty($this->dataSourceArray) || is_null($this->dataSourceArray)) {
                $entityRow = '<tr><td';

                $colspan = count($this->actions) + count($this->columns);

                if(!is_null($this->headerCheckbox)) {
                    $colspan += 1;
                }

                $entityRow .= ' colspan="' . $colspan . '" id="grid-empty-message">' . $this->emptyDataSourceMessage . '</td></tr>';
                if($this->displayNoEntriesMessage === TRUE) {
                    $entityRows[] = $entityRow;
                }
            } else {
                foreach($this->dataSourceArray as $entity) {
                    $entityRow = '<tr>';
    
                    if(!is_null($this->renderRowCheckbox)) {
                        $entityRow .= '<td>' . call_user_func($this->renderRowCheckbox, $entity) . '</td>';
                    }
        
                    foreach($this->actions as $action) {
                        $entityRow .= '<td>' . $action($entity) . '</td>';
                    }
        
                    foreach($this->columns as $varName => $title) {
                        $objectVarName = ucfirst($varName);
        
                        if(method_exists($entity, 'get' . $objectVarName)) {
                            if(array_key_exists($varName, $this->callbacks)) {
                                $entityRow .= '<td>' . $this->callbacks[$varName]($entity) . '</td>';
                            } else {
                                $entityRow .= '<td>' . ($entity->{'get' . $objectVarName}() ?? '-') . '</td>';
                            }
                        } else {
                            if(array_key_exists($varName, $this->callbacks)) {
                                $entityRow .= '<td>' . $this->callbacks[$varName]($entity) . '</td>';
                            } else {
                                $entityRow .= '<td style="background-color: red">' . $varName . '</td>';
                            }
                        }
                    }
        
                    $entityRow .= '</tr>';
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
            $code .= $entityRow;
        }
        // end of data

        $code .= '</table>';

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
    public function createGridControls(string $jsHandlerName, int $page, int $lastPage, int $userId) {
        $firstButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

        if($page == 0) {
            $firstButton .= '0, ' . $userId . ')" disabled>';
        } else {
            $firstButton .= '0, ' . $userId . ')">';
        }

        $firstButton .= '&lt;&lt;</button>';

        $previousButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

        if($page == 0) {
            $previousButton .= '0, ' . $userId . ')" disabled>';
        } else {
            $previousButton .= ($page - 1) . ', ' . $userId . ')">';
        }

        $previousButton .= '&lt;</button>';

        $nextButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

        if(($page + 1) >= $lastPage) {
            $nextButton .= $lastPage . ', ' . $userId . ')" disabled>';
        } else {
            $nextButton .= ($page + 1) . ', ' . $userId . ')">';
        }

        $nextButton .= '&gt;</button>';

        $lastButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

        if(($page + 1) >= $lastPage) {
            $lastButton .= $lastPage . ', ' . $userId . ')" disabled>';
        } else {
            $lastButton .= $lastPage . ', ' . $userId . ')">';
        }

        $lastButton .= '&gt;&gt;</button>';
        
        $code = $firstButton . $previousButton . $nextButton . $lastButton;

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
    public function createGridControls2(string $jsHandlerName, int $page, int $lastPage, array $otherArguments = []) {
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
}

?>