<?php

use App\Constants\SuggestionCategory;
use App\Constants\SuggestionStatus;
use App\Helpers\DateTimeFormatHelper;

require_once('Ajax.php');

function getSuggestions() {
    global $app;

    $limit = (int)(httpGet('limit'));
    $offset = (int)(httpGet('offset'));
    $filterType = httpGet('filterType');
    $filterKey = httpGet('filterKey');

    $suggestions = [];
    $suggestionCount = 0;

    switch($filterType) {
        case 'null':
            $suggestions = $app->suggestionRepository->getOpenSuggestionsForList($limit, $offset);
            $suggestionCount = $app->suggestionRepository->getOpenSuggestionCount();
            break;

        case 'category':
            $suggestions = $app->suggestionRepository->getOpenSuggestionsForListFilterCategory($filterKey, $limit, $offset);
            $suggestionCount = count($suggestions);
            break;

        case 'status':
            $suggestions = $app->suggestionRepository->getSuggestionsForListFilterStatus($filterKey, $limit, $offset);
            $suggestionCount = count($suggestions);
            break;

        case 'user':
            $suggestions = $app->suggestionRepository->getOpenSuggestionsForListFilterAuthor($filterKey, $limit, $offset);
            $suggestionCount = count($suggestions);
            break;
    }


    if(empty($suggestions)) {
        return json_encode(['suggestions' => '<p class="post-text" id="center">No suggestions found.</p>', 'loadMoreLink' => '']);
    }

    $code = [];

    if($filterType != 'null') {
        $name = '';
        $link = '';

        switch($filterType) {
            case 'category':
                $name = 'Category';
                $link = '<span style="color: ' . SuggestionCategory::getColorByKey($filterKey) . '">' . SuggestionCategory::toString($filterKey) . '</span>';
                break;

            case 'status':
                $name = 'Status';
                $link = '<span style="color: ' . SuggestionStatus::getColorByStatus($filterKey) . '">' . SuggestionStatus::toString($filterKey) . '</span>';
                break;

            case 'user':
                $user = $app->userRepository->getUserById($filterKey);
                $name = 'Author';
                $link = $user->getUsername();
                break;
        }

        $code[] = '
            <div class="row">
                <div class="col-md">
                    <p class="post-text">Filter</p>
                    <p class="post-data">' . $name . ': ' . $link . '</p>
                    <a class="post-data-link" href="?page=AdminModule:FeedbackSuggestions&action=list">Clear filter</a>
                </div>
            </div>
            <hr>
        ';
    }

    foreach($suggestions as $suggestion) {
        $author = $app->userRepository->getUserById($suggestion->getUserId());
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $author->getId() . '">Profile</a>';

        $categoryFilterLink = '<a class="post-data-link" style="color: ' . SuggestionCategory::getColorByKey($suggestion->getCategory()) . '" href="?page=AdminModule:FeedbackSuggestions&action=list&filterType=category&filterKey=' . $suggestion->getCategory() . '">' . SuggestionCategory::toString($suggestion->getCategory()) . '</a>';
        $statusFilterLink = '<a class="post-data-link" style="color: ' . SuggestionStatus::getColorByStatus($suggestion->getStatus()) . '" href="?page=AdminModule:FeedbackSuggestions&action=list&filterType=status&filterKey=' . $suggestion->getStatus() . '">' . SuggestionStatus::toString($suggestion->getStatus()) . '</a>';
        $authorFilterLink = '<a class="post-data-link" href="?page=AdminModule:FeedbackSuggestions&action=list&filterType=user&filterKey=' . $suggestion->getUserId() . '">' . $author->getUsername() . '</a>';

        $suggestionLink = '<a class="post-title-link" href="?page=AdminModule:FeedbackSuggestions&action=profile&suggestionId=' . $suggestion->getId() . '">' . $suggestion->getTitle() . '</a>';
        
        $code[] = '
            <div class="row">
                <div class="col-md">
                    <p class="post-title">' . $suggestionLink . '</p>
                    <p class="post-text">' . $suggestion->getShortenedText(100) . '</p>
                    <p class="post-data">Category: ' . $categoryFilterLink . ' Status: ' . $statusFilterLink . ' Author: ' . $authorFilterLink . ' (' . $authorLink . ')</p>
                </div>
            </div>
        ';
    }

    if(($offset + $limit) >= $suggestionCount) {
        $loadMoreLink = '';
    } else {
        $loadMoreLink = '<a class="post-data-link" style="cursor: pointer" onclick="loadSuggestions(' . $limit . ', ' . ($offset + $limit) . ', ' . $app->currentUser->getId() . ')">Load more</a>';
    }

    return json_encode(['suggestions' => implode('', $code), 'loadMoreLink' => $loadMoreLink]);
}

function getSuggestionComments() {
    global $app;

    $suggestionId = httpGet('suggestionId');
    $limit = httpGet('limit');
    $offset = httpGet('offset');

    $comments = $app->suggestionRepository->getCommentsForSuggestion($suggestionId, $limit, $offset);
    $commentCount = $app->suggestionRepository->getCommentCountForSuggestion($suggestionId);

    if(empty($comments)) {
        return json_encode(['comments' => 'No data found', 'loadMoreLink' => '']);
    }

    $commentCode = [];
    foreach($comments as $comment) {
        if($comment->isAdminOnly() && !$app->currentUser->isAdmin()) continue;
        
        $author = $app->userRepository->getUserById($comment->getUserId());
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $comment->getUserId() . '">' . $author->getUsername() . '</a>';

        $hiddenLink = '<a class="post-data-link" style="color: grey" href="?page=AdminModule:FeedbackSuggestions&action=updateComment&commentId=' . $comment->getId() . '&hidden=0&suggestionId=' . $suggestionId . '">Hidden</a>';
        $publicLink = '<a class="post-data-link" style="color: grey" href="?page=AdminModule:FeedbackSuggestions&action=updateComment&commentId=' . $comment->getId() . '&hidden=1&suggestionId=' . $suggestionId . '">Public</a>';
        $hide = $comment->isAdminOnly() ? $hiddenLink : $publicLink;

        $tmp = '
            <div id="comment-' . $comment->getId() . '">
                <p class="post-data">' . $comment->getText() . '</p>
                <p class="post-data">Author: ' . $authorLink . ' Date: ' . DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated()) . ' ' . $hide . '</p>
            </div>
        ';

        $commentCode[] = $tmp;
    }

    $loadMoreLink = '';
    if(($offset + $limit) >= $commentCount) {
        $loadMoreLink = '';
    } else {
        $loadMoreLink = '<a class="post-data-link" style="cursor: pointer" onclick="loadFeedbackSuggestionComments(' . $suggestionId . ', ' . $limit . ', ' . ($offset + $limit) . ', ' . $app->currentUser->getId() . ')">Load more</a>';
    }

    return json_encode(['comments' => implode('<hr>', $commentCode), 'loadMoreLink' => $loadMoreLink]);
}

?>