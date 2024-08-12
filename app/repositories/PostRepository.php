<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\Datetypes\DateTime;
use App\Entities\PostEntity;
use App\Logger\Logger;
use QueryBuilder\QueryBuilder;

class PostRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getLatestPostsForTopicId(string $topicId, int $limit = 5, int $offset = 0, bool $deletedOnly = true) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('topicId = ?', [$topicId])
            ->orderBy('dateCreated', 'DESC');

        if($deletedOnly) {
            $qb->andWhere('isDeleted = 0');
        }   
        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $posts = [];
        while($row = $qb->fetchAssoc()) {
            $posts[] = PostEntity::createEntityFromDbRow($row);
        }

        return $posts;
    }

    public function getLatestMostLikedPostsForTopicId(string $topicId, int $count = 5) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('topicId = ?', [$topicId])
            ->andWhere('isDeleted = 0')
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
            ->andWhere('isDeleted = 0')
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

    public function unlikePost(string $userId, string $postId) {
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

    public function likePost(string $userId, string $postId) {
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

    public function checkLike(string $userId, string $postId) {
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

    public function bulkCheckLikes(string $userId, array $postIds) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['postId'])
            ->from('post_likes')
            ->where($qb->getColumnInValues('postId', $postIds))
            ->andWhere('userId = ?', [$userId])
            ->execute();

        $results = [];
        while($row = $qb->fetchAssoc()) {
            $results[] = $row['postId'];
        }

        return $results;
    }

    public function getLikes(string $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['likes'])
            ->from('posts')
            ->where('postId = ?', [$postId])
            ->execute();

        return $qb->fetch('likes');
    }

    public function getPostIdsForTopicId(string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['postId'])
            ->from('posts')
            ->where('topicId = ?', [$topicId])
            ->andWhere('isDeleted = 0')
            ->execute();

        $posts = [];
        while($row = $qb->fetchAssoc()) {
            $posts[] = $row['postId'];
        }

        return $posts;
    }

    public function getPostCountForTopicId(string $topicId, bool $deletedOnly) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(postId) AS cnt'])
            ->from('posts')
            ->where('topicId = ?', [$topicId]);

        if($deletedOnly) {
            $qb->andWhere('isDeleted = 0');
        }

        $qb->execute();

        return $qb->fetch('cnt') ?? 0;
    }

    public function createNewPost(string $postId, string $topicId, string $authorId, string $title, string $text, string $tag) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('posts', ['postId', 'topicId', 'authorId', 'title', 'description', 'tag'])
            ->values([$postId, $topicId, $authorId, $title, $text, $tag])
            ->execute();

        return $qb->fetch();
    }

    public function getPostById(string $postId): PostEntity|null {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('postId = ?', [$postId]);

        $entity = $this->cache->loadCache($postId, function() use ($qb) {
            $row = $qb->execute()->fetch();

            $entity = PostEntity::createEntityFromDbRow($row);

            return $entity;
        }, 'posts', __METHOD__);

        return $entity;
    }

    public function getPostCountForUserId(string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(postId) AS cnt'])
            ->from('posts')
            ->where('authorId = ?', [$userId])
            ->andWhere('isDeleted = 0')
            ->execute();

        return $qb->fetch('cnt') ?? 0;
    }

    public function updatePost(string $postId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('posts')
            ->set($data)
            ->where('postId = ?', [$postId])
            ->execute();

        return $qb->fetch();
    }

    public function getPostCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(postId) AS cnt'])
            ->from('posts')
            ->andWhere('isDeleted = 0')
            ->execute();

        return $qb->fetch('cnt') ?? 0;
    }

    public function deletePost(string $postId, bool $hide = true) {
        if($hide) {
            $date = new DateTime();
            return $this->updatePost($postId, ['isDeleted' => '1', 'dateDeleted' => $date->getResult()]);
        } else {
            $qb = $this->qb(__METHOD__);

            $qb ->delete()
                ->from('posts')
                ->where('postId = ?', [$postId])
                ->execute();
            
            return $qb->fetch();
        }
    }

    public function getDeletedPostsCount() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(postId) AS cnt'])
            ->from('posts')
            ->where('isDeleted = 1')
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getDeletedPostsForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('isDeleted = 1');

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        return $this->createPostsArrayFromQb($qb);
    }

    public function createPostsArrayFromQb(QueryBuilder $qb) {
        $posts = [];

        while($row = $qb->fetchAssoc()) {
            $posts[] = PostEntity::createEntityFromDbRow($row);
        }

        return $posts;
    }

    public function composeQueryForPosts() {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts');

        return $qb;
    }

    public function getPostsForGrid(int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('isDeleted = 0');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        return $this->createPostsArrayFromQb($qb);
    }

    public function getLikeCount(string $postId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(userId) AS cnt'])
            ->from('post_likes')
            ->where('postId = ?', [$postId])
            ->execute();

        return $qb->fetch('cnt');
    }

    public function getLastCreatedPostInTopicByUserId(string $topicId, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('topicId = ?', [$topicId])
            ->andWhere('authorId = ?', [$userId])
            ->orderBy('dateCreated', 'DESC')
            ->limit(1)
            ->execute();

        return PostEntity::createEntityFromDbRow($qb->fetch());
    }

    public function bulkGetPostsByIds(array $ids) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where($qb->getColumnInValues('postId', $ids))
            ->execute();
        
        return $this->createPostsArrayFromQb($qb);
    }

    public function getPostsCreatedByUser(string $userId, string $maxDate) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('posts')
            ->where('authorId = ?', [$userId])
            ->andWhere('dateCreated >= ?', [$maxDate])
            ->orderBy('dateCreated', 'DESC')
            ->execute();

        $posts = [];
        while($row = $qb->fetchAssoc()) {
            $posts[] = PostEntity::createEntityFromDbRow($row);
        }

        return $posts;
    }

    public function getTopicIdsWithMostPostsInLast24Hrs(int $limit) {
        $dateLimit = new DateTime();
        $dateLimit->modify('-1d');
        $dateLimit = $dateLimit->getResult();

        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(postId) AS cnt', 'topicId'])
            ->from('posts')
            ->where('dateCreated >= ?', [$dateLimit])
            ->groupBy('topicId')
            ->orderBy('cnt', 'DESC');

        if($limit > 0) {
            $qb->limit($limit);
        }

        $qb->execute();

        $data = [];
        while($row = $qb->fetchAssoc()) {
            $data[$row['topicId']] = $row['cnt'];
        }

        return $data;
    }
}

?>