<?php

use App\Components\PostLister\PostLister;
use App\Entities\PostCommentEntity;
use App\Helpers\BannedWordsHelper;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;

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

    $topic = $app->topicRepository->getTopicById($topicId);

    $posts = $app->postRepository->getLatestPostsForTopicId($topicId, $limit, $offset, !$topic->isDeleted());
    $postCount = $app->postRepository->getPostCountForTopicId($topicId, !$topic->isDeleted());

    $code = [];

    if(empty($posts)) {
        return json_encode(['posts' => '<p class="post-text" id="center">No posts found. Create one!</p>', 'loadMoreLink' => '']);
    }

    $bwh = new BannedWordsHelper($app->contentRegulationRepository);

    foreach($posts as $post) {
        $author = $app->userRepository->getUserById($post->getAuthorId());
        $userProfileLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $author->getId() . '">' . $author->getUsername() . '</a>';

        $title = $bwh->checkText($post->getTitle());

        $postLink = '<a class="post-title-link" href="?page=UserModule:Posts&action=profile&postId=' . $post->getId() . '">' . $title . '</a>';

        $liked = $app->postRepository->checkLike($app->currentUser->getId(), $post->getId());
        $likeLink = '<a class="post-like" style="cursor: pointer" onclick="likePost(' . $post->getId() .', ' . $app->currentUser->getId() . ', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';

        $shortenedText = $bwh->checkText($post->getShortenedText(100));

        $tmp = [
            '<div class="row" id="post-' . $post->getId() . '">',
            '<div class="col-md">',
            '<p class="post-title">' . $postLink . '</p>',
            '<hr>',
            '<p class="post-text">' . $shortenedText . '</p>',
            '<hr>',
            '<p class="post-data">Likes: <span id="post-' . $post->getId() . '-likes">' . $post->getLikes() . '</span> <span id="post-' . $post->getId() . '-link">' . $likeLink . '</span>',
            ' | Author: ' . $userProfileLink . ' | Date: ' . DateTimeFormatHelper::formatDateToUserFriendly($post->getDateCreated()) . '</p>',
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

    $post = $app->postRepository->getPostById($postId);

    $comments = $app->postCommentRepository->getLatestCommentsForPostId($postId, $limit, $offset, !$post->isDeleted());
    $commentCount = $app->postCommentRepository->getCommentCountForPostId($postId, !$post->isDeleted());

    $code = [];

    if(empty($comments)) {
        return json_encode(['comments' => '<p class="post-data">No comments found.</p>', 'loadMoreLink' => '']);
    }

    foreach($comments as $comment) {
        $code[] = _createPostComment($postId, $comment);
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

function createNewPostCommentForm() {
    $postId = httpGet('postId');
    $parentCommentId = httpGet('parentCommentId');

    $fb = new FormBuilder();

    $fb ->setAction(['page' => 'UserModule:Posts', 'action' => 'newComment', 'postId' => $postId, 'parentCommentId' => $parentCommentId])
        ->addTextArea('text', 'Comment:', null, true)
        ->addSubmit('Post')
    ;

    return json_encode(['code' => $fb->render()]);
}

function _createPostComment(int $postId, PostCommentEntity $comment, bool $parent = true) {
    global $app;

    $bwh = new BannedWordsHelper($app->contentRegulationRepository);

    $post = $app->postRepository->getPostById($postId);

    $author = $app->userRepository->getUserById($comment->getAuthorId());
    $userProfileLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $author->getId() . '">' . $author->getUsername() . '</a>';

    $liked = $app->postCommentRepository->checkLike($app->currentUser->getId(), $comment->getId());
    if(!$post->isDeleted()) {
        $likeLink = '<a class="post-like" style="cursor: pointer" onclick="likePostComment(' . $comment->getId() .', ' . $app->currentUser->getId() . ', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';
    } else {
        $likeLink = '';
    }

    $childComments = $app->postCommentRepository->getLatestCommentsForCommentId($postId, $comment->getId());
    $childCommentsCode = [];

    if(!empty($childComments)) {
        foreach($childComments as $cc) {
            $childCommentsCode[] = _createPostComment($postId, $cc, false);
        }
    }

    if(!$post->isDeleted()) {
        $reportForm = ' | <a class="post-data-link" href="?page=UserModule:Posts&action=reportComment&commentId=' . $comment->getId() . '">Report</a>';
    } else {
        $reportForm = '';
    }
    $deleteLink = '';
    
    if($app->actionAuthorizator->canDeleteComment($app->currentUser->getId()) && !$post->isDeleted()) {
        $deleteLink = ' | <a class="post-data-link" href="?page=UserModule:Posts&action=deleteComment&commentId=' . $comment->getId() . '&postId=' . $postId . '">Delete</a>';
    }

    $text = $bwh->checkText($comment->getText());

    $code = '
        <div class="row' . ($parent ? '' : ' post-comment-border') . '" id="post-comment-' . $comment->getId() . '">
            ' . ($parent ? '' : '<div class="col-md-1"></div>') . '
            <div class="col-md">
                <div>
                    <p class="post-text">' . $text . '</p>
                    <p class="post-data">Likes: <span id="post-comment-' . $comment->getId() . '-likes">' . $comment->getLikes() . '</span> <span id="post-comment-' . $comment->getId() . '-link">' . $likeLink . '</span>
                                          | Author: ' . $userProfileLink . ' | Date: ' . DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated()) . '' . $reportForm . $deleteLink . '
                    </p>
                    ' . ($post->isDeleted() ? '' : '<a class="post-data-link" id="post-comment-' . $comment->getId() . '-add-comment-link" style="cursor: pointer" onclick="createNewCommentForm(' . $comment->getId() . ', ' . $app->currentUser->getId() . ', ' . $postId . ')">Add comment</a>') . '
                </div>
                <div class="row">
                    <div class="col-md-2"></div>

                    <div class="col-md" id="form">
                        <div id="post-comment-' . $comment->getId() . '-comment-form"></div>
                    </div>
                    
                    <div class="col-md-2"></div>
                </div>
                ' . implode('', $childCommentsCode) .  '
                ' . ($parent ? '' : '<div class="col-md-1"></div>') . '
            </div>
        </div>
        <br>
    ';

    return $code;
}

exit;

?>