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

    public function getCommentsForPostId(string $postId, int $limit = 0, int $offset = 0) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('postId = ?', [$postId])
            ->andWhere('isDeleted = 0');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = PostCommentEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function getLatestCommentsForPostId(string $postId, int $limit = 0, int $offset = 0, bool $deletedOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('postId = ?', [$postId])
            ->andWhere('parentCommentId IS NULL')
            ->orderBy('dateCreated', 'DESC');

        if($deletedOnly) {
            $qb->andWhere('isDeleted = 0');
        }

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entities[] = PostCommentEntity::createEntityFromDbRow($row);
        }

        return $entities;
    }

    public function createNewComment(string $commentId, string $postId, string $authorId, string $text, ?string $parentCommentId = null) {
        $qb = $this->qb(__METHOD__);

        $keys = ['commentId', 'postId', 'authorId', 'commentText'];
        $values = [$commentId, $postId, $authorId, $text];

        if($parentCommentId !== null) {
            $keys[] = 'parentCommentId';
            $values[] = $parentCommentId;
        }

        $qb ->insert('post_comments', $keys)
            ->values($values)
            ->execute();

        return $qb->fetch();
    }

    public function getCommentCountForPostId(string $postId, bool $deletedOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(commentId) AS cnt'])
            ->from('post_comments')
            ->where('postId = ?', [$postId])
            ->andWhere('parentCommentId IS NULL');

        if($deletedOnly) {
            $qb->andWhere('isDeleted = 0');
        }

        $qb->execute();

        return $qb->fetch('cnt');
    }

    public function checkLike(string $userId, string $commentId) {
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

    public function getLikes(string $commentId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['likes'])
            ->from('post_comments')
            ->where('commentId = ?', [$commentId])
            ->execute();

        return $qb->fetch('likes');
    }

    public function likeComment(string $userId, string $commentId) {
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

    public function unlikeComment(string $userId, string $commentId) {
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

    public function getLatestCommentsForCommentId(string $postId, string $commentId) {
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

    public function getCommentById(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('commentId = ?', [$id])
            ->execute();

        return PostCommentEntity::createEntityFromDbRow($qb->fetch());
    }

    public function updateComment(string $id, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('post_comments')
            ->set($data)
            ->where('commentId = ?', [$id])
            ->execute();

        return $qb->fetch();
    }

    public function deleteComment(string $commentId, bool $hide = true) {
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

    public function getLikedCommentsForUser(string $userId, array $commentIds) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['commentId'])
            ->from('post_comment_likes')
            ->where('userId = ?', [$userId])
            ->andWhere($qb->getColumnInValues('commentId', $commentIds))
            ->execute();

        $result = [];
        while($row = $qb->fetchAssoc()) {
            $result[] = $row['commentId'];
        }

        return $result;
    }

    public function getCommentsThatHaveAParent(string $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('postId = ?', [$postId])
            ->andWhere('parentCommentId IS NOT NULL')
            ->execute();

        $comments = [];
        while($row = $qb->fetchAssoc()) {
            $comments[] = PostCommentEntity::createEntityFromDbRow($row);
        }

        return $comments;
    }

    public function getCommentsForUser(string $userId, string $maxDate) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('post_comments')
            ->where('authorId = ?', [$userId])
            ->andWhere('dateCreated >= ?', [$maxDate])
            ->execute();

        $comments = [];
        while($row = $qb->fetchAssoc()) {
            if($row === null) {
                continue;
            }
            $comments[] = PostCommentEntity::createEntityFromDbRow($row);
        }

        return $comments;
    }

    public function getPostIdsWithMostCommentsInLast24Hrs(int $limit) {
        $dateLimit = new DateTime();
        $dateLimit->modify('-1d');
        $dateLimit = $dateLimit->getResult();
        
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(commentId) AS cnt', 'postId'])
            ->from('post_comments')
            ->where('dateCreated >= ?', [$dateLimit])
            ->groupBy('postId')
            ->orderBy('cnt', 'DESC');

        if($limit > 0) {
            $qb->limit($limit);
        }

        $qb->execute();

        $data = [];
        while($row = $qb->fetchAssoc()) {
            $data[$row['postId']] = $row['cnt'];
        }

        return $data;
    }
}

?>