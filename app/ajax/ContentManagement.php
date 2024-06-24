<?php

use App\Constants\ReportEntityType;
use App\Entities\PostCommentEntity;
use App\Entities\PostEntity;
use App\Entities\TopicEntity;
use App\Helpers\DateTimeFormatHelper;
use App\UI\GridBuilder\GridBuilder;
use App\UI\LinkBuilder;

require_once('Ajax.php');

function getDeletedContent() {
    global $app;

    $page = (int)(httpGet('page'));
    $filter = httpGet('filter');

    $gridSize = $app->cfg['GRID_SIZE'];

    $gb = new GridBuilder();
    $lastPage = null;

    $reports = $app->reportRepository->getAllReports();
    $checkReport = function(int $entityId, string $entityType) use ($reports) {
        foreach($reports as $report) {
            if($report->getEntityType() == $entityType && $report->getEntityId() == $entityId) {
                return $report;
            }
        }

        return null;
    };

    switch($filter) {
        case 'topics':
            $data = $app->topicRepository->getDeletedTopicsForGrid($gridSize, ($gridSize * $page));
            $lastPage = ceil($app->topicRepository->getDeletedTopicCount() / $gridSize);

            $gb->addDataSource($data);
            $gb->addColumns(['title' => 'Title', 'reported' => 'Reported?', 'dateDeleted' => 'Deleted']);
            $gb->addOnColumnRender('reported', function(TopicEntity $topic) use ($checkReport) {
                $report = $checkReport($topic->getId(), ReportEntityType::TOPIC);

                if($report === null) {
                    return '<span style="color: red">No</span>';
                } else {
                    $link = '<a class="post-data-link" style="color: green" href="?page=AdminModule:FeedbackReports&action=profile&reportId=' . $report->getId() . '">Yes</a>';
                    return $link;
                }
            });
            $gb->addOnColumnRender('dateDeleted', function(TopicEntity $topic) {
                return DateTimeFormatHelper::formatDateToUserFriendly($topic->getDateDeleted()) ?? '-';
            });
            $gb->addOnColumnRender('title', function(TopicEntity $topic) {
                return LinkBuilder::createSimpleLink($topic->getTitle(), ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topic->getId()], 'post-data-link');
            });

            break;

        case 'posts':
            $data = $app->postRepository->getDeletedPostsForGrid($gridSize, ($gridSize * $page));
            $lastPage = ceil($app->postRepository->getDeletedPostsCount() / $gridSize);

            $gb->addDataSource($data);
            $gb->addColumns(['title' => 'Title', 'reported' => 'Reported?', 'dateDeleted' => 'Deleted']);
            $gb->addOnColumnRender('reported', function(PostEntity $post) use ($checkReport) {
                $report = $checkReport($post->getId(), ReportEntityType::POST);

                if($report === null) {
                    return '<span style="color: red">No</span>';
                } else {
                    $link = '<a class="post-data-link" style="color: green" href="?page=AdminModule:FeedbackReports&action=profile&reportId=' . $report->getId() . '">Yes</a>';
                    return $link;
                }
            });
            $gb->addOnColumnRender('dateDeleted', function(PostEntity $post) {
                return DateTimeFormatHelper::formatDateToUserFriendly($post->getDateDeleted()) ?? '-';
            });
            $gb->addOnColumnRender('title', function(PostEntity $post) {
                return LinkBuilder::createSimpleLink($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'post-data-link');
            });

            break;

        case 'comments':
            $data = $app->postCommentRepository->getDeletedComments();
            $lastPage = ceil($app->postCommentRepository->getDeletedCommentCount() / $gridSize);

            $gb->addDataSource($data);
            $gb->addColumns(['post' => 'Post', 'text' => 'Text', 'reported' => 'Reported?', 'dateDeleted' => 'Deleted']);
            $gb->addOnColumnRender('post', function(PostCommentEntity $comment) use ($app) {
                $post = $app->postRepository->getPostById($comment->getPostId());
                return LinkBuilder::createSimpleLink($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'post-data-link');
            });
            $gb->addOnColumnRender('text', function(PostCommentEntity $comment) {
                return $comment->getShortenedText();
            });
            $gb->addOnColumnRender('reported', function(PostCommentEntity $comment) use ($checkReport) {
                $report = $checkReport($comment->getId(), ReportEntityType::COMMENT);

                if($report === null) {
                    return '<span style="color: red">No</span>';
                } else {
                    $link = '<a class="post-data-link" style="color: green" href="?page=AdminModule:FeedbackReports&action=profile&reportId=' . $report->getId() . '">Yes</a>';
                    return $link;
                }
            });
            $gb->addOnColumnRender('dateDeleted', function(PostCommentEntity $comment) {
                return DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateDeleted()) ?? '-';
            });

            break;
    }

    $paginator = _createDeletedContentGridControls('getDeletedContent', $page, $lastPage, $app->currentUser->getId(), $filter);

    return json_encode(['grid' => $gb->build(), 'paginator' => $paginator]);
}

function _createDeletedContentGridControls(string $jsHandlerName, int $page, int $lastPage, int $userId, string $filter) {
    $firstButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

    if($page == 0) {
        $firstButton .= '0, ' . $userId . ',\'' . $filter . '\')" disabled>';
    } else {
        $firstButton .= '0, ' . $userId . ',\'' . $filter . '\')">';
    }

    $firstButton .= '&lt;&lt;</button>';

    $previousButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

    if($page == 0) {
        $previousButton .= '0, ' . $userId . ',\'' . $filter . '\')" disabled>';
    } else {
        $previousButton .= ($page - 1) . ', ' . $userId . ',\'' . $filter . '\')">';
    }

    $previousButton .= '&lt;</button>';

    $nextButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

    if(($page + 1) >= $lastPage) {
        $nextButton .= $lastPage . ', ' . $userId . ',\'' . $filter . '\')" disabled>';
    } else {
        $nextButton .= ($page + 1) . ', ' . $userId . ',\'' . $filter . '\')">';
    }

    $nextButton .= '&gt;</button>';

    $lastButton = '<button type="button" class="grid-control-button" onclick="' . $jsHandlerName . '(';

    if(($page + 1) >= $lastPage) {
        $lastButton .= $lastPage . ', ' . $userId . ',\'' . $filter . '\')" disabled>';
    } else {
        $lastButton .= $lastPage . ', ' . $userId . ',\'' . $filter . '\')">';
    }

    $lastButton .= '&gt;&gt;</button>';
        
    $code = $firstButton . $previousButton . $nextButton . $lastButton;

    return $code;
}

?>