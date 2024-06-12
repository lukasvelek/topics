<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Entities\PostEntity;
use App\Logger\Logger;

class PostRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getLatestPostsForTopicId(int $topicId, int $count = 5, int $offset = 0) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('topicId = ?', [$topicId])
            ->orderBy('dateCreated', 'DESC');
            
        if($count > 0) {
            $qb->limit($count);
        }

        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $posts = [];
        while($row = $qb->fetchAssoc()) {
            $posts[] = PostEntity::createEntityFromDbRow($row);
        }

        return $posts;
    }

    public function getLatestMostLikedPostsForTopicId(int $topicId, int $count = 5) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('topicId = ?', [$topicId])
            ->orderBy('likes', 'DESC')
            ->orderBy('dateCreated', 'DESC');

        if($count > 0) {
            $qb->limit($count);
        }

        $qb->execute();

        $posts = [];
        while($row = $qb->fetchAssoc()) {
            $posts[] = PostEntity::createEntityFromDbRow($row);
        }

        return $posts;
    }

    public function getLatestMostLikedPostsForTopicIds(array $topicIds, int $count) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where($qb->getColumnInValues('topicId', $topicIds))
            ->orderBy('likes', 'DESC')
            ->orderBy('dateCreated', 'DESC');

        if($count > 0) {
            $qb->limit($count);
        }

        $qb->execute();

        $posts = [];
        while($row = $qb->fetchAssoc()) {
            $posts[] = PostEntity::createEntityFromDbRow($row);
        }

        return $posts;
    }

    public function unlikePost(int $userId, int $postId) {
        $result = false;

        // check
        $result = $this->checkLike($userId, $postId);
        
        if($result === false) {
            return false;
        }
        
        $likes = $this->getLikes($postId);
        
        $qb = $this->qb(__METHOD__);

        // delete entry

        $qb ->delete()
            ->from('post_likes')
            ->where('postId = ?', [$postId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        $qb->clean();

        // update

        $qb ->update('posts')
            ->set(['likes' => $likes - 1])
            ->where('postId = ?', [$postId])
            ->execute();

        return true;
    }

    public function likePost(int $userId, int $postId) {
        $result = false;
        
        // check
        $result = $this->checkLike($userId, $postId);
        
        if($result === true) {
            return false;
        }

        $likes = $this->getLikes($postId);
        
        $qb = $this->qb(__METHOD__);

        // create entry

        $qb ->insert('post_likes', ['postId', 'userId'])
            ->values([$postId, $userId])
            ->execute();

        $qb->clean();

        // update

        $qb ->update('posts')
            ->set(['likes' => $likes + 1])
            ->where('postId = ?', [$postId])
            ->execute();

        return true;
    }

    public function checkLike(int $userId, int $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['postId'])
            ->from('post_likes')
            ->where('postId = ?', [$postId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        $row = $qb->fetch('postId');

        if($row !== NULL) {
            return true;
        }

        return false;
    }

    public function getLikes(int $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['likes'])
            ->from('posts')
            ->where('postId = ?', [$postId])
            ->execute();

        return $qb->fetch('likes');
    }

    public function getPostIdsForTopicId(int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['postId'])
            ->from('posts')
            ->where('topicId = ?', [$topicId])
            ->execute();

        $posts = [];
        while($row = $qb->fetchAssoc()) {
            $posts[] = $row['postId'];
        }

        return $posts;
    }

    public function getPostCountForTopicId(int $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(postId) AS cnt'])
            ->from('posts')
            ->where('topicId = ?', [$topicId])
            ->execute();

        return $qb->fetch('cnt') ?? 0;
    }

    public function createNewPost(int $topicId, int $authorId, string $title, string $text) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('posts', ['topicId', 'authorId', 'title', 'description'])
            ->values([$topicId, $authorId, $title, $text])
            ->execute();

        return $qb->fetch();
    }

    public function getPostById(int $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('postId = ?', [$postId]);

        $entity = CacheManager::loadCache($postId, function() use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = PostEntity::createEntityFromDbRow($row);

            return $entity;
        }, 'posts');

        return $entity;
    }

    public function getPostCountForUserId(int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(postId) AS cnt'])
            ->from('posts')
            ->where('authorId = ?', [$userId])
            ->execute();

        return $qb->fetch('cnt') ?? 0;
    }
}

?>