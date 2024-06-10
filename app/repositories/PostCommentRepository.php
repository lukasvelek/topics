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

    public function getLatestCommentsForPostId(int $postId, int $limit = 0, int $offset = 0) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('postId = ?', [$postId])
            ->orderBy('dateCreated', 'DESC');

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

    public function createNewComment(int $postId, int $authorId, string $text) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('post_comments', ['postId', 'authorId', 'commentText'])
            ->values([$postId, $authorId, $text])
            ->execute();

        return $qb->fetch();
    }

    public function getCommentCountForPostId(int $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(commentId) AS cnt'])
            ->from('post_comments')
            ->where('postId = ?', [$postId])
            ->execute();

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

        $this->logger->info('Like check result: ' . ($result ? 'true' : 'false'), __METHOD__);
        
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
}

?>