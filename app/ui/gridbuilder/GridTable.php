<?php

namespace App\UI\GridBuilder;

use App\Helpers\RenderableElementHelper;
use App\UI\IRenderable;

class Table implements IRenderable {
    private array $rows;
    private ?Row $headerRow;
    private int $border;

    public function __construct() {
        $this->rows = [];
        $this->headerRow = null;
        $this->border = 1;
    }

    public function addRow(Row $row, bool $isHeader = false) {
        if($isHeader) {
            $this->headerRow = $row;
        } else {
            $this->rows[] = $row;
        }
    }

    public function bulkAddRows(array $rows) {
        $this->rows = array_merge($this->rows, $rows);
    }

    public function render() {
        $code = '<div class="row"><div class="col-md"><table border="' . $this->border . '" id="gridbuilder-grid">';

        $code .= '<thead>' . $this->headerRow->render() . '</thead>';
        $code .= '<tbody>' . RenderableElementHelper::implodeAndRender('', $this->rows) . '</tbody>';

        $code .= '</table></div></div>';

        return $code;
    }
}

?>