<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportEntityType;
use App\Core\AjaxRequestBuilder;
use App\Entities\PostCommentEntity;
use App\Entities\PostEntity;
use App\Entities\TopicEntity;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class ManageDeletedContentPresenter extends AAdminPresenter {
    private GridHelper $gridHelper;

    public function __construct() {
        parent::__construct('ManageDeletedContentPresenter', 'Deleted content management');
    }

    public function startup() {
        parent::startup();
        
        $this->gridHelper = new GridHelper($this->logger, $this->getUserId());
    }
    
    public function actionListGrid() {
        $gridPage = $this->httpGet('gridPage');
        $filter = $this->httpGet('gridFilter');

        $gridSize = $this->app->getGridSize();
        $lastPage = null;

        $page = $this->gridHelper->getGridPage(GridHelper::GRID_DELETED_CONTENT, $gridPage);

        $gb = new GridBuilder();

        $reports = $this->app->reportRepository->getAllReports();
        $checkReport = function(string $entityId, string $entityType) use ($reports) {
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
                $data = $this->app->topicRepository->getDeletedTopicsForGrid($gridSize, ($gridSize * $page));
                $totalCount = $this->app->topicRepository->getDeletedTopicCount();
                $lastPage = ceil($totalCount / $gridSize);

                $gb->addDataSource($data);
                $gb->addColumns(['title' => 'Title', 'reported' => 'Reported?', 'dateDeleted' => 'Deleted']);
                $gb->addOnColumnRender('reported', function(Cell $cell, TopicEntity $topic) use ($checkReport) {
                    $report = $checkReport($topic->getId(), ReportEntityType::TOPIC);

                    if($report === null) {
                        $cell->setTextColor('red');
                        $cell->setValue('No');
                    } else {
                        $link = $this->createFullURLString('AdminModule:FeedbackReports', 'profile', ['reportId' => $report->getId()]);
                        $a = HTML::a();

                        $a->href($link)
                        ->text('Yes')
                        ->class('grid-link');

                        $cell->setTextColor('green');
                        $cell->setValue($a);
                    }

                    return $cell;
                });
                $gb->addOnColumnRender('dateDeleted', function(Cell $cell, TopicEntity $topic) {
                    $cell->setValue(DateTimeFormatHelper::formatDateToUserFriendly($topic->getDateDeleted()));
                    $cell->setTitle(DateTimeFormatHelper::formatDateToUserFriendly($topic->getDateDeleted(), DateTimeFormatHelper::ATOM_FORMAT));
                    return $cell;
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
                $data = $this->app->postRepository->getDeletedPostsForGrid($gridSize, ($gridSize * $page));
                $totalCount = $this->app->postRepository->getDeletedPostsCount();
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
                $data = $this->app->postCommentRepository->getDeletedComments();
                $totalCount = $this->app->postCommentRepository->getDeletedCommentCount();
                $lastPage = ceil($totalCount / $gridSize);

                $gb->addDataSource($data);
                $gb->addColumns(['post' => 'Post', 'text' => 'Text', 'reported' => 'Reported?', 'dateDeleted' => 'Deleted']);
                $gb->addOnColumnRender('post', function(Cell $cell, PostCommentEntity $comment) {
                    $post = $this->app->postRepository->getPostById($comment->getPostId());
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

        return ['grid' => $gb->build()];
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
        $this->addScript('getDeletedContent(-1, \'' . $filter . '\')');

        $links = [];

        switch($filter) {
            case 'topics':
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(-1, \'topics\')" style="cursor: pointer" href="#" id="filter-btn-topics">Filter topics</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(-1, \'posts\')" style="cursor: pointer" href="#" id="filter-btn-posts">Filter posts</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(0, \'comments\')" style="cursor: pointer" href="#" id="filter-btn-comments">Filter comments</a>&nbsp;';
                break;

            case 'posts':
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(-1, \'topics\')" style="cursor: pointer" href="#" id="filter-btn-topics">Filter topics</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(-1, \'posts\')" style="cursor: pointer" href="#" id="filter-btn-posts">Filter posts</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(-1, \'comments\')" style="cursor: pointer" href="#" id="filter-btn-comments">Filter comments</a>&nbsp;';
                break;

            case 'comments':
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(-1, \'topics\')" style="cursor: pointer" href="#" id="filter-btn-topics">Filter topics</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(-1, \'posts\')" style="cursor: pointer" href="#" id="filter-btn-posts">Filter posts</a>&nbsp;';
                $links[] = '<a class="post-data-link" onclick="getDeletedContent(-1, \'comments\')" style="cursor: pointer" href="#" id="filter-btn-comments">Filter comments</b>&nbsp;';
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