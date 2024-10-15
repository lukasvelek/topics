<?php

namespace App\UI\GridBuilder2;

use App\Exceptions\AException;
use App\UI\AComponent;
use App\UI\FormBuilder\FormBuilder;
use App\UI\ModalBuilder\ModalBuilder;
use Exception;
use QueryBuilder\QueryBuilder;

class GridExportModal extends ModalBuilder {
    private QueryBuilder $dataSource;
    private string $gridComponentName;

    public function __construct(AComponent $grid) {
        parent::__construct($grid->httpRequest, $grid->cfg);

        $this->setId('grid-export');
        $this->setTitle('Grid export');

        $this->gridComponentName = $grid->componentName;
    }

    public function setDataSource(QueryBuilder $qb) {
        $this->dataSource = $qb;
    }

    public function render() {
        $this->setContentFromFormBuilder($this->createForm());
        return parent::render();
    }

    public function setGridComponentName(string $gridComponentName) {
        $this->gridComponentName = $gridComponentName;
    }

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