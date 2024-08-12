<?php

namespace App\Services;

use App\Core\CacheManager;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Repositories\PostRepository;
use Exception;

class PostLikeEqualizerService extends AService {
    private const POST_BATCH_SIZE = 100;

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

            foreach($posts as $post) {
                $likes = $this->getPostLikes($post->getId());

                if($post->getLikes() > $likes) {
                    $this->logInfo(sprintf('Post #%d has like mismatch. Likes in the `posts` table: %d and likes in the `post_likes` table: %d.', $post->getId(), $post->getLikes(), $likes));
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

    private function getPostLikes(string $postId) {
        return $this->pr->getLikeCount($postId);
    }

    private function updateLikes(string $postId, int $likes) {
        $this->pr->updatePost($postId, ['likes' => $likes]);
    }

    private function invalidateCache() {
        $cm = new CacheManager($this->logger);
        $cm->invalidateCache('posts');
    }
}

?>