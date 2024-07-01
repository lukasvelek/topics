<?php

namespace App\Managers;

use App\Core\CacheManager;
use App\Logger\Logger;
use App\Repositories\PostCommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\TopicRepository;

class ContentManager extends AManager {
    private const T_TOPIC = 1;
    private const T_POST = 2;
    private const T_COMMENT = 3;

    private TopicRepository $topicRepository;
    private PostRepository $postRepository;
    private PostCommentRepository $postCommentRepository;

    private bool $fullDelete;

    public function __construct(TopicRepository $topicRepository, PostRepository $postRepository, PostCommentRepository $postCommentRepository, bool $fullDelete, Logger $logger) {
        parent::__construct($logger);
        
        $this->topicRepository = $topicRepository;
        $this->postCommentRepository = $postCommentRepository;
        $this->postRepository = $postRepository;

        $this->fullDelete = $fullDelete;
    }

    public function deleteTopic(int $topicId, bool $deleteCache = true) {
        $posts = $this->postRepository->getLatestPostsForTopicId($topicId, 0);

        foreach($posts as $post) {
            $this->deletePost($post->getId(), false);
        }

        $this->topicRepository->deleteTopic($topicId, $this->isHide());
        $this->topicRepository->removeAllTopicFollows($topicId);

        $this->afterDelete(self::T_TOPIC, $deleteCache);
        $this->afterDelete(self::T_POST, $deleteCache);
    }

    public function deletePost(int $postId, bool $deleteCache = true) {
        $comments = $this->postCommentRepository->getCommentsForPostId($postId);

        foreach($comments as $comment) {
            $this->deleteComment($comment->getId(), $deleteCache);
        }

        $this->postRepository->deletePost($postId, $this->isHide());

        $this->afterDelete(self::T_POST, $deleteCache);
    }

    public function deleteComment(int $commentId, bool $deleteCache = true) {
        $this->postCommentRepository->deleteComment($commentId, $this->isHide());

        $this->afterDelete(self::T_COMMENT, $deleteCache);
    }

    private function isHide() {
        return !$this->fullDelete;
    }

    private function afterDelete(int $type, bool $deleteCache) {
        if($deleteCache) {
            switch($type) {
                case self::T_POST:
                    CacheManager::invalidateCache('posts');
                    break;
                
                case self::T_TOPIC:
                    CacheManager::invalidateCache('topics');
                    break;
            }
        }
    }
}

?>