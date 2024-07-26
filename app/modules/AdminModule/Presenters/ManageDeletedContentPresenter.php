<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportEntityType;
use App\Core\AjaxRequestBuilder;
use App\Entities\PostCommentEntity;
use App\Entities\PostEntity;
use App\Entities\TopicEntity;
use App\Helpers\DateTimeFormatHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageDeletedContentPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('ManageDeletedContentPresenter', 'Deleted content management');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createManageSidebar();
        });
    }
    
    public function actionListGrid() {
        global $app;

        $page = $this->httpGet('gridPage');
        $filter = $this->httpGet('gridFilter');

        $gridSize = $gridSize = $app->getGridSize();
        $lastPage = null;

        $gb = new GridBuilder();

        $reports = $app->reportRepository->getAllReports();
        $checkReport = function(int $entityId, string $entityType) use ($reports) {
            foreach($reports as $report) {
                if($report->getEntityType() == $entityType && $report->getEntityId() == $entityId) {
                    return $report;
                }
            }

            return null;
        };

        $totalCount = 0;

        switch($filter) {
            case 'topics':
                $data = $app->topicRepository->getDeletedTopicsForGrid($gridSize, ($gridSize * $page));
                $totalCount = $app->topicRepository->getDeletedTopicCount();
                $lastPage = ceil($totalCount / $gridSize);

                $gb->addDataSource($data);
                $gb->addColumns(['title' => 'Title', 'reported' => 'Reported?', 'dateDeleted' => 'Deleted']);
                $gb->addOnColumnRender('reported', function(Cell $cell, TopicEntity $topic) use ($checkReport) {
                    $report = $checkReport($topic->getId(), ReportEntityType::TOPIC);

                    if($report === null) {
                        $cell->setTextColor('red');
                        $cell->setValue('No');
                    } else {
                        $a = HTML::a();

                        $a->href($this->createFullURLString('AdminModule:FeedbackReports', 'profile', ['reportId' => $report->getId()]))
                            ->text('Yes')
                            ->class('grid-link');

                        return $a->render();
                    }
                });
                $gb->addOnColumnRender('dateDeleted', function(Cell $cell, TopicEntity $topic) {
                    return DateTimeFormatHelper::formatDateToUserFriendly($topic->getDateDeleted()) ?? '-';
                });
                $gb->addOnColumnRender('title', function(Cell $cell, TopicEntity $topic) {
                    $a = HTML::a();

                    $a->href($this->createFullURLString('UserModule:Topics', 'profile', ['topicId' => $topic->getId()]))
                        ->text($topic->getTitle())
                        ->class('grid-link');
                        
                    return $a->render();
                });

                break;

            case 'posts':
                $data = $app->postRepository->getDeletedPostsForGrid($gridSize, ($gridSize * $page));
                $totalCount = $app->postRepository->getDeletedPostsCount();
                $lastPage = ceil($totalCount / $gridSize);

                $gb->addDataSource($data);
                $gb->addColumns(['title' => 'Title', 'reported' => 'Reported?', 'dateDeleted' => 'Deleted']);
                $gb->addOnColumnRender('reported', function(Cell $cell, PostEntity $post) use ($checkReport) {
                    $report = $checkReport($post->getId(), ReportEntityType::POST);

                    if($report === null) {
                        $cell->setTextColor('red');
                        $cell->setValue('red');
                        return $cell;
                    } else {
                        $a = HTML::a();

                        $a->href($this->createFullURLString('AdminModule:FeedbackReports', 'profile', ['reportId' => $report->getId()]))
                            ->text('Yes')
                            ->class('grid-link');

                        return $a->render();
                    }
                });
                $gb->addOnColumnRender('dateDeleted', function(Cell $cell, PostEntity $post) {
                    return DateTimeFormatHelper::formatDateToUserFriendly($post->getDateDeleted()) ?? '-';
                });
                $gb->addOnColumnRender('title', function(Cell $cell, PostEntity $post) {
                    $a = HTML::a();

                    $a->href($this->createFullURLString('UserModule:Posts', 'profile', ['postId' => $post->getId()]))
                        ->text($post->getTitle())
                        ->class('grid-link');
                    
                    return $a->render();
                });

                break;

            case 'comments':
                $data = $app->postCommentRepository->getDeletedComments();
                $totalCount = $app->postCommentRepository->getDeletedCommentCount();
                $lastPage = ceil($totalCount / $gridSize);

                $gb->addDataSource($data);
                $gb->addColumns(['post' => 'Post', 'text' => 'Text', 'reported' => 'Reported?', 'dateDeleted' => 'Deleted']);
                $gb->addOnColumnRender('post', function(Cell $cell, PostCommentEntity $comment) use ($app) {
                    $post = $app->postRepository->getPostById($comment->getPostId());
                    return LinkBuilder::createSimpleLink($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'grid-link');
                });
                $gb->addOnColumnRender('text', function(Cell $cell, PostCommentEntity $comment) {
                    return $comment->getShortenedText();
                });
                $gb->addOnColumnRender('reported', function(Cell $cell, PostCommentEntity $comment) use ($checkReport) {
                    $report = $checkReport($comment->getId(), ReportEntityType::COMMENT);

                    if($report === null) {
                        $cell->setTextColor('red');
                        $cell->setValue('No');
                        return $cell;
                    } else {
                        $a = HTML::a();

                        $a->href($this->createFullURLString('AdminModule:FeedbackReports', 'profile', ['reportId' => $report->getId()]))
                            ->text('Yes')
                            ->class('grid-link');

                        return $a->render();
                    }
                });
                $gb->addOnColumnRender('dateDeleted', function(Cell $cell, PostCommentEntity $comment) {
                    return DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateDeleted()) ?? '-';
                });

                break;
        }

        $gb->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getDeletedContent', [$filter]);

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }

    public function handleList() {
        $filter = $this->httpGet('filter') ?? 'topics';

        $arb = new AjaxRequestBuilder();

        $arb->setMethod('GET')
            ->setURL(['page' => 'AdminModule:ManageDeletedContent', 'action' => 'listGrid'])
            ->setFunctionName('getDeletedContent')
            ->setFunctionArguments(['_page', '_filter'])
            ->setHeader(['gridPage' => '_page', 'gridFilter' => '_filter'])
            ->updateHTMLElement('grid-content', 'grid')
            ->addWhenDoneOperation('
                if(_filter == "topics") {
                    $("#filter-btn-topics").css("font-weight", "bold");
                    $("#filter-btn-posts").css("font-weight", "normal");
                    $("#filter-btn-comments").css("font-weight", "normal");
                } else if(_filter == "posts") {
                    $("#filter-btn-topics").css("font-weight", "normal");
                    $("#filter-btn-posts").css("font-weight", "bold");
                    $("#filter-btn-comments").css("font-weight", "normal");
                } else {
                    $("#filter-btn-topics").css("font-weight", "normal");
                    $("#filter-btn-posts").css("font-weight", "normal");
                    $("#filter-btn-comments").css("font-weight", "bold");
                }
            ')
        ;

        $this->addScript($arb->build());
        $this->addScript('getDeletedContent(0, \'' . $filter . '\')');

        $links = [];

        switch($filter) {
            case 'topics':
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'topics\')" style="cursor: pointer" href="#" id="filter-btn-topics">Filter topics</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'posts\')" style="cursor: pointer" href="#" id="filter-btn-posts">Filter posts</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'comments\')" style="cursor: pointer" href="#" id="filter-btn-comments">Filter comments</a>&nbsp;';
                break;

            case 'posts':
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'topics\')" style="cursor: pointer" href="#" id="filter-btn-topics">Filter topics</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'posts\')" style="cursor: pointer" href="#" id="filter-btn-posts">Filter posts</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'comments\')" style="cursor: pointer" href="#" id="filter-btn-comments">Filter comments</a>&nbsp;';
                break;

            case 'comments':
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'topics\')" style="cursor: pointer" href="#" id="filter-btn-topics">Filter topics</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'posts\')" style="cursor: pointer" href="#" id="filter-btn-posts">Filter posts</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'comments\')" style="cursor: pointer" href="#" id="filter-btn-comments">Filter comments</b>&nbsp;';
                break;
        }

        $this->saveToPresenterCache('links', $links);
    }

    public function renderList() {
        $links = $this->loadFromPresenterCache('links');
        $this->template->links = $links;
    }
}

?>