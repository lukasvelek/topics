<?php

namespace App\Rpeositories;

use App\Core\DatabaseConnection;
use App\Logger\Logger;
use App\Repositories\ARepository;

class MailRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createEntry(string $id, string $recipient, string $title, string $content) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('mail_queue', ['mailId', 'recipient', 'title', 'content'])
            ->values([$id, $recipient, $title, $content])
            ->execute();

        return $qb->fetchBool();
    }
}

?>