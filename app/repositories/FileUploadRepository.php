<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\PostImageFileEntity;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

class FileUploadRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createNewEntry(string $uploadId, string $filename, string $filepath, string $userId, string $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('post_file_uploads', ['uploadId', 'filename', 'filepath', 'userId', 'postId'])
            ->values([$uploadId, $filename, $filepath, $userId, $postId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getBulkFilesForPost(array $postIds) {
        $qb = $this->composeQueryForFiles(__METHOD__);

        $qb ->where($qb->getColumnInValues('postId', $postIds))
            ->execute();

        return $this->createEntitiesFromQb($qb);
    }

    public function getFilesForPost(string $postId) {
        $qb = $this->composeQueryForFiles(__METHOD__);

        $qb ->where('postId = ?', [$postId])
            ->execute();

        return $this->createEntitiesFromQb($qb);
    }

    public function getAllFiles() {
        $qb = $this->composeQueryForFiles(__METHOD__);

        $qb->execute();

        return $this->createEntitiesFromQb($qb);
    }

    public function getAllFilesForGrid(int $limit, int $offset) {
        $qb = $this->composeQueryForFiles(__METHOD__);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        return $this->createEntitiesFromQb($qb);
    }

    private function composeQueryForFiles(string $method = __METHOD__) {
        $qb = $this->qb($method);

        $qb ->select(['*'])
            ->from('post_file_uploads');

        return $qb;
    }

    private function createEntitiesFromQb(QueryBuilder $qb) {
        $files = [];
        while($row = $qb->fetchAssoc()) {
            $files[] = PostImageFileEntity::createEntityFromDbRow($row);
        }
        
        return $files;
    }

    public function getFileById(string $id) {
        $qb = $this->composeQueryForFiles(__METHOD__);

        $qb->where('uploadId = ?', [$id])
            ->execute();

        return PostImageFileEntity::createEntityFromDbRow($qb->fetch());
    }

    public function deleteFileUploadById(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->delete()
            ->from('post_file_uploads')
            ->where('uploadId = ?', [$id])
            ->execute();

        return $qb->fetchBool();
    }

    public function getFilesForPostForGrid(string $postId, int $limit, int $offset) {
        $qb = $this->composeQueryForFiles(__METHOD__);
        
        $qb->where('postId = ?', [$postId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        return $this->createEntitiesFromQb($qb);
    }

    public function getFilesForUserForGrid(string $userId, int $limit, int $offset) {
        $qb = $this->composeQueryForFiles(__METHOD__);
        
        $qb->where('userId = ?', [$userId]);

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        return $this->createEntitiesFromQb($qb);
    }

    public function getPostIdsWithFileUploads() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['postId'])
            ->from('post_file_uploads')
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            if(!in_array($row['postId'], $ids)) {
                $ids[] = $row['postId'];
            }
        }

        return $ids;
    }

    public function getUserIdsWithFileUploads() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['userId'])
            ->from('post_file_uploads')
            ->execute();

        $ids = [];
        while($row = $qb->fetchAssoc()) {
            if(!in_array($row['userId'], $ids)) {
                $ids[] = $row['userId'];
            }
        }

        return $ids;
    }
}

?>