<?php

use App\Components\PostLister\PostLister;

require_once('Ajax.php');

function likePost() {
    global $app;

    $userId = (int)(httpPost('userId'));
    $postId = (int)(httpPost('postId'));
    $toLike = httpPost('like');

    $liked = false;
    
    if($toLike == 'true') {
        // like
        $app->postRepository->likePost($userId, $postId);
        $liked = true;
    } else {
        // unlike
        $app->postRepository->unlikePost($userId, $postId);
    }
        
    $likes = $app->postRepository->getLikes($postId);
    
    return json_encode(['link' => PostLister::createLikeLink($userId, $postId, $liked), 'likes' => $likes]);
}

function loadPostsForTopic() {
    global $app;

    $topicId = (int)(httpGet('topicId'));
    $limit = (int)(httpGet('limit'));
    $offset = (int)(httpGet('offset'));

    $posts = $app->postRepository->getLatestPostsForTopicId($topicId, $limit, $offset);
    $postCount = $app->postRepository->getPostCountForTopicId($topicId);

    $code = [];

    if(empty($posts)) {
        return json_encode(['posts' => implode('', $code), 'loadMoreLink' => '']);
    }

    foreach($posts as $post) {
        $author = $app->userRepository->getUserById($post->getAuthorId());
        $userProfileLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $author->getId() . '">' . $author->getUsername() . '</a>';

        $postLink = '<a class="post-title-link" href="?page=UserModule:Posts&action=profile&postId=' . $post->getId() . '">' . $post->getTitle() . '</a>';

        $liked = $app->postRepository->checkLike($app->currentUser->getId(), $post->getId());
        $likeLink = '<a class="post-like" style="cursor: pointer" onclick="likePost(' . $post->getId() .', ' . $app->currentUser->getId() . ', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';

        $tmp = [
            '<div class="row" id="post-' . $post->getId() . '">',
            '<div class="col-md">',
            '<p class="post-title">' . $postLink . '</p>',
            '<hr>',
            '<p class="post-text">' . $post->getShortenedText(100) . '</p>',
            '<hr>',
            '<p class="post-data">Likes: <span id="post-' . $post->getId() . '-likes">' . $post->getLikes() . '</span> <span id="post-' . $post->getId() . '-link">' . $likeLink . '</span>',
            ' | Author: ' . $userProfileLink . '</p>',
            '</div></div><br>'
        ];

        $code[] = implode('', $tmp);
    }

    if(($offset + $limit) >= $postCount) {
        $loadMoreLink = '';
    } else {
        $loadMoreLink = '<a class="post-data-link" style="cursor: pointer" onclick="loadPostsForTopic(' . $topicId . ', ' . $limit . ', ' . ($offset + $limit) . ', ' . $app->currentUser->getId() . ')">Load more</a>';
    }

    return json_encode(['posts' => implode('', $code), 'loadMoreLink' => $loadMoreLink]);
}

function loadCommentsForPost() {
    global $app;

    $postId = (int)(httpGet('postId'));
    $limit = (int)(httpGet('limit'));
    $offset = (int)(httpGet('offset'));

    $comments = $app->postCommentRepository->getLatestCommentsForPostId($postId, $limit, $offset);
    $commentCount = $app->postCommentRepository->getCommentCountForPostId($postId);

    $code = [];

    if(empty($comments)) {
        return json_encode(['comments' => implode('', $code), 'loadMoreLink' => '']);
    }

    foreach($comments as $comment) {
        $author = $app->userRepository->getUserById($comment->getAuthorId());
        $userProfileLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $author->getId() . '">' . $author->getUsername() . '</a>';

        $liked = $app->postCommentRepository->checkLike($app->currentUser->getId(), $comment->getId());
        $likeLink = '<a class="post-like" style="cursor: pointer" onclick="likePostComment(' . $comment->getId() .', ' . $app->currentUser->getId() . ', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';

        $tmp = [
            '<div class="row" id="post-comment-' . $comment->getId() . '">',
            '<div class="col-md">',
            '<p class="post-text">' . $comment->getText() . '</p>',
            '<hr>',
            '<p class="post-data">Likes: <span id="post-comment-' . $comment->getId() . '-likes">' . $comment->getLikes() . '</span> <span id="post-comment-' . $comment->getId() . '-link">' . $likeLink . '</span>',
            ' | Author: ' . $userProfileLink . '</p>',
            '</div></div><br>'
        ];

        $code[] = implode('', $tmp);
    }

    if(($offset + $limit) >= $commentCount) {
        $loadMoreLink = '';
    } else {
        $loadMoreLink = '<a class="post-data-link" style="cursor: pointer" onclick="loadCommentsForPost(' . $postId . ', ' . $limit . ', ' . ($offset + $limit) . ', ' . $app->currentUser->getId() . ')">Load more</a>';
    }

    return json_encode(['posts' => implode('', $code), 'loadMoreLink' => $loadMoreLink]);
}

function likePostComment() {
    global $app;

    $userId = (int)(httpPost('userId'));
    $commentId = (int)(httpPost('commentId'));
    $toLike = httpPost('like');

    $liked = false;
    
    if($toLike == 'true') {
        // like
        $app->postCommentRepository->likeComment($userId, $commentId);
        $liked = true;
    } else {
        // unlike
        $app->postCommentRepository->unlikeComment($userId, $commentId);
    }
        
    $likes = $app->postCommentRepository->getLikes($commentId);
    
    $link = '<a class="post-like" style="cursor: pointer" onclick="likePostComment(' . $commentId .', ' . $app->currentUser->getId() . ', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';

    return json_encode(['link' => $link, 'likes' => $likes]);
}

exit;

?>