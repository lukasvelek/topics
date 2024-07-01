<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\Datetypes\DateTime;
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
            ->where('postId = ?', [$postId])
            ->andWhere('isDeleted = 0');

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

    public function getLatestCommentsForPostId(int $postId, int $limit = 0, int $offset = 0, bool $deletedOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('postId = ?', [$postId])
            ->andWhere('parentCommentId IS NULL')
            ->orderBy('dateCreated', 'DESC');

        if($deletedOnly) {
            $qb->andWhere('isDeleted = 0');
        }

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

    public function createNewComment(int $postId, int $authorId, string $text, ?int $parentCommentId = null) {
        $qb = $this->qb(__METHOD__);

        $keys = ['postId', 'authorId', 'commentText'];
        $values = [$postId, $authorId, $text];

        if($parentCommentId !== null) {
            $keys[] = 'parentCommentId';
            $values[] = $parentCommentId;
        }

        $qb ->insert('post_comments', $keys)
            ->values($values)
            ->execute();

        return $qb->fetch();
    }

    public function getCommentCountForPostId(int $postId, bool $deletedOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(commentId) AS cnt'])
            ->from('post_comments')
            ->where('postId = ?', [$postId])
            ->execute();

        if($deletedOnly) {
            $qb->andWhere('isDeleted = 0');
        }

        return $qb->fetch('cnt');
    }

    public function checkLike(int $userId, int $commentId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['commentId'])
            ->from('post_comment_likes')
            ->where('commentId = ?', [$commentId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        $row = $qb->fetch('commentId');

        if($row !== NULL) {
            return true;
        }

        return false;
    }

    public function getLikes(int $commentId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['likes'])
            ->from('post_comments')
            ->where('commentId = ?', [$commentId])
            ->execute();

        return $qb->fetch('likes');
    }

    public function likeComment(int $userId, int $commentId) {
        $result = false;
        
        // check
        $result = $this->checkLike($userId, $commentId);
        
        if($result === true) {
            return false;
        }

        $likes = $this->getLikes($commentId);
        
        $qb = $this->qb(__METHOD__);

        // create entry

        $qb ->insert('post_comment_likes', ['commentId', 'userId'])
            ->values([$commentId, $userId])
            ->execute();

        $qb->clean();

        // update

        $qb ->update('post_comments')
            ->set(['likes' => $likes + 1])
            ->where('commentId = ?', [$commentId])
            ->execute();

        return true;
    }

    public function unlikeComment(int $userId, int $commentId) {
        $result = false;
        
        // check
        $result = $this->checkLike($userId, $commentId);
        
        if($result === false) {
            return false;
        }

        $likes = $this->getLikes($commentId);

        $this->logger->info('Like count: ' . $likes, __METHOD__);
        
        $qb = $this->qb(__METHOD__);

        // create entry

        $qb ->delete()
            ->from('post_comment_likes')
            ->where('commentId = ?', [$commentId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        $qb->clean();

        // update

        $qb ->update('post_comments')
            ->set(['likes' => $likes - 1])
            ->where('commentId = ?', [$commentId])
            ->execute();

        return true;
    }

    public function getLatestCommentsForCommentId(int $postId, int $commentId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('parentCommentId = ?', [$commentId])
            ->andWhere('postId = ?', [$postId])
            ->andWhere('isDeleted = 0')
            ->orderBy('dateCreated', 'DESC')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = PostCommentEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getCommentById(int $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('commentId = ?', [$id])
            ->execute();

        return PostCommentEntity::createEntityFromDbRow($qb->fetch());
    }

    public function updateComment(int $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('post_comments')
            ->set($data)
            ->where('commentId = ?', [$id])
            ->execute();

        return $qb->fetch();
    }

    public function deleteComment(int $commentId, bool $hide = true) {
        if($hide) {
            $date = new DateTime();
            return $this->updateComment($commentId, ['isDeleted' => '1', 'dateDeleted' => $date->getResult()]);
        } else {
            $qb = $this->qb(__METHOD__);

            $qb ->delete()
                ->from('post_comments')
                ->where('commentId = ?', [$commentId])
                ->execute();

            return $qb->fetch();
        }
    }

    public function getDeletedComments() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('isDeleted = 1')
            ->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = PostCommentEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getDeletedCommentCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(commentId) AS cnt'])
            ->from('post_comments')
            ->where('isDeleted = 1')
            ->execute();

        return $qb->fetch('cnt');
    }

    public function composeQueryForPostComments() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments');

        return $qb;
    }
}

?>