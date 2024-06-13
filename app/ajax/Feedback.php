<?php

use App\Constants\ReportCategory;
use App\Constants\ReportEntityType;
use App\Constants\ReportStatus;
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

    switch($filterType) {
        case 'null':
            $suggestions = $app->suggestionRepository->getOpenSuggestionsForList($limit, $offset);
            break;

        case 'category':
            $suggestions = $app->suggestionRepository->getOpenSuggestionsForListFilterCategory($filterKey, $limit, $offset);
            break;

        case 'status':
            $suggestions = $app->suggestionRepository->getSuggestionsForListFilterStatus($filterKey, $limit, $offset);
            break;

        case 'user':
            $suggestions = $app->suggestionRepository->getOpenSuggestionsForListFilterAuthor($filterKey, $limit, $offset);
            break;
    }

    $suggestionCount = count($suggestions);

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
        $loadMoreLink = '<a class="post-data-link" style="cursor: pointer" onclick="loadSuggestions(' . $limit . ', ' . ($offset + $limit) . ', ' . $app->currentUser->getId() . ', \'' . $filterType . '\', \'' . $filterKey . '\')">Load more</a>';
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

        $deleteLink = ' <a class="post-data-link" style="color: red" href="?page=AdminModule:FeedbackSuggestions&action=deleteComment&commentId=' . $comment->getId() . '&suggestionId=' . $suggestionId . '">Delete</a>';
        $delete = ($app->currentUser->isAdmin() && !$comment->isStatusChange()) ? $deleteLink : '';

        $tmp = '
            <div id="comment-' . $comment->getId() . '">
                <p class="post-data">' . $comment->getText() . '</p>
                <p class="post-data">Author: ' . $authorLink . ' Date: ' . DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated()) . ' ' . $hide . '' . $delete . '</p>
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

function getReports() {
    global $app;

    $limit = (int)(httpGet('limit'));
    $offset = (int)(httpGet('offset'));
    $filterType = httpGet('filterType');
    $filterKey = httpGet('filterKey');

    $reports = [];

    switch($filterType) {
        case 'null':
            $reports = $app->reportRepository->getOpenReportsForList($limit, $offset);
            break;

        case 'user':
            $reports = $app->reportRepository->getOpenReportsForListFilterUser($filterKey, $limit, $offset);
            break;

        case 'category':
            $reports = $app->reportRepository->getOpenReportsForListFilterCategory($filterKey, $limit, $offset);
            break;

        case 'status':
            $reports = $app->reportRepository->getReportsForListFilterStatus($filterKey, $limit, $offset);
            break;
    }

    $reportCount = count($reports);

    if(empty($reports)) {
        return json_encode(['reports' => '<p class="post-text" id="center">No reports found</p>', 'loadMoreLink' => '']);
    }

    $code = [];

    if($filterType != 'null') {
        $name = '';
        $link = '';

        switch($filterType) {
            case 'category':
                $name = 'Category';
                $link = ReportCategory::toString($filterKey);
                break;

            case 'status':
                $name = 'Status';
                $link = ReportStatus::toString($filterKey);
                break;

            case 'user':
                $user = $app->userRepository->getUserById($filterKey);
                $name = 'User';
                $link = $user->getUsername();
                break;
        }

        $code[] = '
            <div class="row">
                <div class="col-md">
                    <p class="post-text">Filter</p>
                    <p class="post-data">' . $name . ': ' . $link . '</p>
                    <a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=list">Clear filter</a>
                </div>
            </div>
            <hr>
        ';
    }

    foreach($reports as $report) {
        $author = $app->userRepository->getUserById($report->getUserId());
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $author->getId() . '">Profile</a>';

        $statusFilterLink = '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=list&filterType=status&filterKey=' . $report->getStatus() . '">' . ReportStatus::toString($report->getStatus()) . '</a>';
        $authorFilterLink = '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=list&filterType=user&filterKey=' . $report->getUserId() . '">' . $author->getUsername() . '</a>';
        $categoryFilterLink = '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=list&filterType=category&filterKey=' . $report->getCategory() . '">' . ReportCategory::toString($report->getCategory()) . '</a>';

        $title = 'Report of ' . ReportEntityType::toString($report->getEntityType());

        $reportLink = '<a class="post-title-link" href="?page=AdminModule:FeedbackReports&action=profile&reportId=' . $report->getId() . '">' . $title . '</a>';

        $code[] = '
            <div class="row">
                <div class="col-md">
                    <p class="post-title">' . $reportLink . '</p>
                    <p class="post-data">Category: ' . $categoryFilterLink . ' Author: ' . $authorFilterLink . ' (' . $authorLink . ') Status: ' . $statusFilterLink . '</p>
                </div>
            </div>
        ';
    }

    if(($offset + $limit) >= $reportCount) {
        $loadMoreLink = '';
    } else {
        $loadMoreLink = '<a class="post-data-link" style="cursor: pointer" onclick="loadReports(' . $limit . ', ' . ($offset + $limit) . ', ' . $app->currentUser->getId() . ', \'' . $filterType . '\', \'' . $filterKey . '\')">Load more</a>';
    }

    return json_encode(['reports' => implode('', $code), 'loadMoreLink' => $loadMoreLink]);
}

function cleanOutputCode(string $code) {
    return preg_replace('/\s+/', ' ', trim($code));
}

?>