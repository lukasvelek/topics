<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;

class TopicCalendarEventRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }
}

?>