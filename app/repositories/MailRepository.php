<?php

namespace App\Rpeositories;

use App\Core\DatabaseConnection;
use App\Entities\EmailEntity;
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

    public function getAllEntriesLimited(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('mail_queue')
            ->where('isSent = 0');

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = EmailEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function deleteEntry(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('mail_queue')
            ->where('mailId = ?', [$id])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateEntry(string $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('mail_queue')
            ->set($data)
            ->where('mailId = ?', [$id])
            ->execute();

        return $qb->fetchBool();
    }
}

?>