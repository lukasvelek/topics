<?php

namespace App\Services;

use App\Core\CacheManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Repositories\PostRepository;
use Exception;

class PostLikeEqualizerService extends AService {
    private const POST_BATCH_SIZE = 1000;

    private PostRepository $pr;

    public function __construct(Logger $logger, ServiceManager $serviceManager, PostRepository $pr) {
        parent::__construct('PostLikeEqualizer', $logger, $serviceManager);

        $this->pr = $pr;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            try {
                $this->serviceStop();
            } catch(AException|Exception $e2) {}
            
            $this->logError($e->getMessage());
            
            throw $e;
        }
    }

    private function innerRun() {
        $postCount = $this->getTotalPostCount();

        $this->logInfo(sprintf('Found %d posts to be processed.', $postCount));

        $offset = 0;
        while($posts = $this->getPosts(self::POST_BATCH_SIZE, (self::POST_BATCH_SIZE * $offset))) {
            $this->logInfo(sprintf('Processing batch #%d with %d posts.', ($offset + 1), count($posts)));

            $postIds = [];
            foreach($posts as $post) {
                $postIds[] = $post->getId();
            }

            $likesArray = $this->getPostLikes($postIds);

            foreach($posts as $post) {
                $likes = $likesArray[$post->getId()];

                if($post->getLikes() > $likes) {
                    $this->logInfo(sprintf('Post #%s has like mismatch. Likes in the `posts` table: %d and likes in the `post_likes` table: %d.', $post->getId(), $post->getLikes(), $likes));
                    $this->updateLikes($post->getId(), $likes);
                }
            }

            $offset++;
        }

        $this->invalidateCache();
    }

    private function getTotalPostCount() {
        return $this->pr->getPostCount();
    }

    private function getPosts(int $limit, int $offset) {
        return $this->pr->getPostsForGrid($limit, $offset);
    }

    private function getPostLikes(array $postIds) {
        return $this->pr->getBulkLikeCount($postIds);
    }

    private function updateLikes(string $postId, int $likes) {
        $this->logger->info('Updating post #' . $postId . '. Setting likes to ' . $likes . '.', __METHOD__);

        try {
            $this->pr->beginTransaction();

            $this->pr->updatePost($postId, ['likes' => $likes]);

            $this->pr->commit(null, __METHOD__);
        } catch(AException $e) {
            $this->pr->rollback();

            throw $e;
        }
    }

    private function invalidateCache() {
        $cm = new CacheManager($this->logger);
        $cm->invalidateCache(CacheManager::NS_POSTS);
    }
}

?>