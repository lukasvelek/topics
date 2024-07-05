<?php

use App\Core\CacheManager;
use App\Exceptions\AException;

const SERVICE_TITLE = 'PostLikeEqualizer';
const POST_BATCH_LIMIT = 100;

require_once('CommonService.php');

function getPostCount() {
    global $app;

    return $app->postRepository->getPostCount();
}

function getPosts(int $limit, int $offset) {
    global $app;

    return $app->postRepository->getPostsForGrid($limit, $offset);
}

function getPostLikes(int $postId) {
    global $app;

    return $app->postRepository->getLikeCount($postId);
}

function updateLikes(int $postId, int $likes) {
    global $app;

    $app->postRepository->updatePost($postId, ['likes' => $likes]);
}

function invalidateCache() {
    global $app;

    $cm = new CacheManager($app->logger);

    $cm->invalidateCache('posts');
}

startService();

try {
    $postCount = getPostCount();

    $offset = 0;
    while($offset < $postCount) {
        $posts = getPosts(POST_BATCH_LIMIT, $offset);

        foreach($posts as $post) {
            $likes = getPostLikes($post->getId());

            if($post->getLikes() > $likes) {
                logInfo('Post #' . $post->getId() . ' has unequal likes -> in posts = ' . $post->getLikes() . ' and in post_likes = ' . $likes . '.');
                updateLikes($post->getId(), $likes);
                logInfo('Likes for post #' . $post->getId() . ' have been updated.');
            }
        }

        $offset += POST_BATCH_LIMIT;
    }

    invalidateCache();
} catch(AException|Exception $e) {
    logError($e->getMessage());
}

stopService();

?>