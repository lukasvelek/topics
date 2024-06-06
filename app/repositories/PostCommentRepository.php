<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Entities\PostCommentEntity;
use App\Logger\Logger;

class PostCommentRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getCommentsForPostId(int $postId, int $limit = 0, int $offset = 0) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('postId = ?', [$postId]);

        if($limit > 0) {
            $qb->limit($limit);
        }

        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = PostCommentEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }
}

?>