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

exit;

?>