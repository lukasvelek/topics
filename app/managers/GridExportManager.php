<?php

namespace App\Managers;

use App\Logger\Logger;
use App\Repositories\GridExportRepository;

class GridExportManager extends AManager {
    private GridExportRepository $ger;

    public function __construct(Logger $logger, EntityManager $entityManager, GridExportRepository $ger) {
        parent::__construct($logger, $entityManager);

        $this->ger = $ger;
    }
}

?>