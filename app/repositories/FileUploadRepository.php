<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\PostImageFileEntity;
use App\Logger\Logger;

class FileUploadRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewEntry(string $uploadId, string $filename, string $filepath, int $userId, int $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('post_file_uploads', ['uploadId', 'filename', 'filepath', 'userId', 'postId'])
            ->values([$uploadId, $filename, $filepath, $userId, $postId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getFilesForPost(int $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_file_uploads')
            ->where('postId = ?', [$postId])
            ->execute();

        $files = [];
        while($row = $qb->fetchAssoc()) {
            $files[] = PostImageFileEntity::createEntityFromDbRow($row);
        }
        
        return $files;
    }
}

?>