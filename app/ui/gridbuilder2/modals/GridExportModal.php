<?php

namespace App\UI\GridBuilder2;

use App\Exceptions\AException;
use App\UI\AComponent;
use App\UI\FormBuilder\FormBuilder;
use App\UI\ModalBuilder\ModalBuilder;
use Exception;
use QueryBuilder\QueryBuilder;

/**
 * Modal that allows controlling exporting from grids
 * 
 * @author Lukas Velek
 */
class GridExportModal extends ModalBuilder {
    private QueryBuilder $dataSource;
    private string $gridComponentName;

    /**
     * Class constructor
     * 
     * @param AComponent $grid Grid
     */
    public function __construct(AComponent $grid) {
        parent::__construct($grid->httpRequest, $grid->cfg);

        $this->setId('grid-export');
        $this->setTitle('Grid export');

        $this->gridComponentName = $grid->componentName;
    }

    /**
     * Sets the data source
     * 
     * @param QueryBuilder $qb QueryBuilder instance
     */
    public function setDataSource(QueryBuilder $qb) {
        $this->dataSource = $qb;
    }

    /**
     * Renders the modal content
     * 
     * @return string HTML code
     */
    public function render() {
        $this->setContentFromFormBuilder($this->createForm());
        return parent::render();
    }

    /**
     * Sets the grid component name
     * 
     * @param string $gridComponentName
     */
    public function setGridComponentName(string $gridComponentName) {
        $this->gridComponentName = $gridComponentName;
    }

    /**
     * Creates the modal form
     * 
     * @return FormBuilder FormBuilder instance
     */
    private function createForm() {
        $fb = new FormBuilder();

        $fb->setMethod();

        if($this->isOverLimit()) {
            $fb->addButton('Export to the limit', $this->gridComponentName . '_exportLimited()');
            $fb->addButton('Export all', $this->gridComponentName . '_exportUnlimited()');
        } else {
            $fb->addButton('Export', $this->gridComponentName . '_exportLimited()');
        }

        return $fb;
    }

    /**
     * Checks if the exporting can be done asynchronously
     * 
     * @return bool True if asynchronous exporting is allowed or false if not
     */
    private function isOverLimit() {
        $ds = clone $this->dataSource;

        try {
            $count = $ds->execute()->fetchAll()->num_rows;
        } catch(AException|Exception $e) {
            $count = 0;
        }
        
        return $count >= $this->cfg['MAX_GRID_EXPORT_SIZE'];
    }
}

?>