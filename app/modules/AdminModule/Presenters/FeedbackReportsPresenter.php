<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportCategory;
use App\Constants\ReportEntityType;
use App\Constants\ReportStatus;
use App\Constants\UserProsecutionType;
use App\Core\AjaxRequestBuilder;
use App\Entities\ReportEntity;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\GridBuilder;
use App\UI\HTML\HTML;

class FeedbackReportsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('FeedbackReportsPresenter', 'Reports');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createFeedbackSidebar();
        });

        global $app;

        if(!$app->sidebarAuthorizator->canManageReports($app->currentUser->getId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Feedback', 'action' => 'dashboard']);
        }
    }

    public function actionReportGrid() {
        global $app;

        $page = $this->httpGet('gridPage');
        $filterType = $this->httpGet('filterType');
        $filterKey = $this->httpGet('filterKey');

        $gridSize = $app->cfg['GRID_SIZE'];

        $reports = [];
        $reportCount = [];

        $filterText = '';

        switch($filterType) {
            case 'null':
                $reports = $app->reportRepository->getOpenReportsForList($gridSize, ($page * $gridSize));
                $reportCount = count($app->reportRepository->getOpenReportsForList(0, 0));
                break;
    
            case 'user':
                $reports = $app->reportRepository->getOpenReportsForListFilterUser($filterKey, $gridSize, ($page * $gridSize));
                $reportCount = count($app->reportRepository->getOpenReportsForListFilterUser($filterKey, 0, 0));
                $filterText = 'User: ' . $app->userRepository->getUserById($filterKey)->getUsername();
                break;
    
            case 'category':
                $reports = $app->reportRepository->getOpenReportsForListFilterCategory($filterKey, $gridSize, ($page * $gridSize));
                $reportCount = count($app->reportRepository->getOpenReportsForListFilterCategory($filterKey, 0, 0));
                $filterText = 'Category: ' . ReportCategory::toString($filterKey);
                break;
    
            case 'status':
                $reports = $app->reportRepository->getReportsForListFilterStatus($filterKey, $gridSize, ($page * $gridSize));
                $reportCount = count($app->reportRepository->getReportsForListFilterStatus($filterKey, 0, 0));
                $filterText = 'Status: ' . ReportStatus::toString($filterKey);
                break;
        }

        $lastPage = ceil($reportCount / $gridSize) - 1;

        $gb = new GridBuilder();

        $gb->addDataSource($reports);
        $gb->addColumns(['title' => 'Title', 'category' => 'Category', 'status' => 'Status', 'user' => 'User']);
        $gb->addOnColumnRender('title', function(ReportEntity $re) {
            return ReportEntityType::toString($re->getEntityType()) . ' report';
        });
        $gb->addOnColumnRender('category', function(ReportEntity $re) {
            $a = HTML::a();

            $a->href('#')
                ->onClick('getReportGrid(0, \'category\', \'' . $re->getCategory() . '\')')
                ->text(ReportCategory::toString($re->getCategory()))
                ->class('post-data-link')
            ;

            return $a->render();
        });
        $gb->addOnColumnRender('status', function(ReportEntity $re) {
            $a = HTML::a();

            $a->href('#')
                ->text(ReportStatus::toString($re->getStatus()))
                ->onClick('getReportGrid(0, \'status\', \'' . $re->getStatus() . '\')')
                ->class('post-data-link')
            ;

            return $a->render();
        });
        $gb->addOnColumnRender('user', function(ReportEntity $re) use ($app) {
            $user = $app->userRepository->getUserById($re->getUserId());

            $a = HTML::a();

            $a->href('#')
                ->text($user->getUsername())
                ->onClick('getReportGrid(0, \'user\', \'' . $user->getId() . '\')')
                ->class('post-data-link')
            ;

            return $a->render();
        });
        $gb->addOnColumnRender('title', function(ReportEntity $re) {
            return '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=profile&reportId=' . $re->getId() . '">' . ReportEntityType::toString($re->getEntityType()) . ' report</a>';
        });

        $paginator = $gb->createGridControls2('getReportGrid', $page, $lastPage, [$filterType, $filterKey]);

        $filterControl = '';
        if($filterType != 'null') {
            $filterControl = $filterText . '&nbsp;<a class="post-data-link" onclick="getReportGrid(0, \'null\', \'null\')" href="#">Clear filter</a>';
        }

        $this->ajaxSendResponse(['grid' => $gb->build(), 'paginator' => $paginator, 'filterControl' => $filterControl]);
    }

    public function handleList() {
        $filterType = $this->httpGet('filterType') ?? 'null';
        $filterKey = $this->httpGet('filterKey') ?? 'null';

        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'AdminModule:FeedbackReports', 'action' => 'reportGrid'])
            ->setMethod('GET')
            ->setHeader(['gridPage' => '_page', 'filterType' => '_filterType', 'filterKey' => '_filterKey'])
            ->setFunctionName('getReportGrid')
            ->setFunctionArguments(['_page', '_filterType', '_filterKey'])
            ->updateHTMLElement('grid-content', 'grid')
            ->updateHTMLElement('grid-paginator', 'paginator')
            ->updateHTMLElement('grid-filter-control', 'filterControl')
        ;

        $this->addScript($arb->build());
        $this->addScript('getReportGrid(0, \'' . $filterType . '\', \'' . $filterKey . '\')');
    }

    public function renderList() {}

    public function handleProfile() {
        global $app;

        $reportId = $this->httpGet('reportId', true);
        $report = $app->reportRepository->getReportById($reportId);

        $this->saveToPresenterCache('report', $report);

        $author = $app->userRepository->getUserById($report->getUserId());
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $report->getUserId() . '">' . $author->getUsername() . '</a>';
        $entityTypeText = ReportEntityType::toString($report->getEntityType());
        $entityLink = '<a class="post-data-link" href="?page=UserModule:';

        switch($report->getEntityType()) {
            case ReportEntityType::COMMENT:
                $comment = $app->postCommentRepository->getCommentById($report->getEntityId());
                $post = $app->postRepository->getPostById($comment->getPostId());
                $author = $app->userRepository->getUserById($comment->getAuthorId());
                $entityLink .= 'Posts&action=profile&postId=' . $comment->getPostId() . '">Comment on post \'' . $post->getTitle() . '\' from user \'' . $author->getUsername() . '\' created on \'' . DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated()) .'\'</a>';
                break;

            case ReportEntityType::POST:
                $post = $app->postRepository->getPostById($report->getEntityId());
                $entityLink .= 'Posts&action=profile&postId=' . $post->getId() . '">' . $post->getTitle() . '</a>';
                break;

            case ReportEntityType::TOPIC:
                $topic = $app->topicRepository->getTopicById($report->getEntityId());
                $entityLink .= 'Topics&action=profile&topicId=' . $topic->getId() . '">' . $topic->getTitle() . '</a>';
                break;

            case ReportEntityType::USER:
                $reportUser = $app->userRepository->getUserById($report->getEntityId());
                $entityLink .= 'Users&action=profile&userId=' . $report->getEntityId() . '">' . $reportUser->getUsername() . '</a>';
                break;
        }

        $data = '
            <div>
                <p class="post-data">Category: ' . ReportCategory::toString($report->getCategory()) . '</p>
                <p class="post-data">Reported by: ' . $authorLink . '</p>
                <p class="post-data">Status: ' . ReportStatus::toString($report->getStatus()) . '</p>
                <p class="post-data">' . $entityTypeText . ': ' . $entityLink . '</p>
                <p class="post-data">Report created: ' . DateTimeFormatHelper::formatDateToUserFriendly($report->getDateCreated()) . '</p>
            </div>
        ';

        $this->saveToPresenterCache('data', $data);

        $resolution = '';

        if($report->getStatusComment() !== null) {
            $resolution = $report->getStatusComment();
        }

        $this->saveToPresenterCache('resolution', $resolution);

        $adminLinks = [];

        if($report->getStatus() == ReportStatus::OPEN) {
            $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=resolutionForm&reportId=' . $report->getId() . '">Create a resolution</a>';

            switch($report->getEntityType()) {
                case ReportEntityType::COMMENT:
                    $comment = $app->postCommentRepository->getCommentById($report->getEntityId());

                    if($comment->isDeleted() !== true) {
                        $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:ManagePosts&action=deleteComment&commentId=' . $report->getEntityId() . '&reportId=' . $report->getId() . '">Delete comment</a>';
                    }

                    break;

                case ReportEntityType::USER:
                    $userProsecution = $app->userProsecutionRepository->getLastProsecutionForUserId($report->getEntityId());

                    if($userProsecution !== null) {
                        if($userProsecution->getType() == UserProsecutionType::PERMA_BAN) break;

                        if( $userProsecution->getType() == UserProsecutionType::WARNING ||
                            ((strtotime($userProsecution->getEndDate()) < time() && ($userProsecution->getType() == UserProsecutionType::BAN)))) {
                            $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:ManageUsers&action=banUser&userId=' . $report->getEntityId() . '&reportId=' . $report->getId() . '">Ban user</a>';
                        } else if($userProsecution->getType() != UserProsecutionType::BAN) {
                            $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:ManageUsers&action=warnUser&userId=' . $report->getEntityId() . '&reportId=' . $report->getId() . '">Warn user</a>';
                        }
                    } else {
                        $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:ManageUsers&action=banUser&userId=' . $report->getEntityId() . '&reportId=' . $report->getId() . '">Ban user</a>';
                        $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:ManageUsers&action=warnUser&userId=' . $report->getEntityId() . '&reportId=' . $report->getId() . '">Warn user</a>';
                    }

                    break;

                case ReportEntityType::POST:
                    $post = $app->postRepository->getPostById($report->getEntityId());

                    if($post->isDeleted() !== true) {
                        $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:ManagePosts&action=deletePost&postId=' . $report->getEntityId() . '&reportId=' . $report->getId() . '">Delete post</a>';
                    }

                    break;

                case ReportEntityType::TOPIC:
                    $topic = $app->topicRepository->getTopicById($report->getEntityId());

                    if($topic->isDeleted() !== true) {
                        $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:ManageTopics&action=deleteTopic&topicId=' . $report->getEntityId() . '&reportId=' . $report->getId() . '">Delete topic</a>';
                    }

                    break;
            }
        } else {
            //$adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=reopen&reportId=' . $report->getId() . '">Reopen</a>';
        }

        $this->saveToPresenterCache('adminLinks', implode('&nbsp;', $adminLinks));
    }

    public function renderProfile() {
        $report = $this->loadFromPresenterCache('report');
        $data = $this->loadFromPresenterCache('data');
        $resolution = $this->loadFromPresenterCache('resolution');
        $adminLinks = $this->loadFromPresenterCache('adminLinks');

        $this->template->title = ReportEntityType::toString($report->getEntityType()) . ' report';
        $this->template->description = $report->getDescription();
        $this->template->data = $data;
        $this->template->resolution = $resolution;
        $this->template->admin_part = $adminLinks;
    }

    public function handleResolutionForm(?FormResponse $fr = null) {
        global $app;

        $reportId = $this->httpGet('reportId', true);
        $report = $app->reportRepository->getReportById($reportId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $comment = $fr->comment;
            $userLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $app->currentUser->getId() . '">' . $app->currentUser->getUsername() . '</a>';
            $text = 'User ' . $userLink . ' closed this report with comment: ' . $comment;

            $app->reportRepository->updateReport($reportId, ['statusComment' => $text, 'status' => ReportStatus::RESOLVED]);
            
            
            $reportLink = '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '">' . ReportEntityType::toString($report->getEntityType()) . ' report</a>';
            $relevantText = 'User ' . $userLink . ' closed report ' . $reportLink . ' that is relevant to this. Thus this report has been closed as well.';
            
            $relevantReports = $app->reportRepository->getRelevantReports($reportId);
            $idReleveantReports = [];
            foreach($relevantReports as $rr) {
                $idReleveantReports[] = $rr->getId();
            }
            
            $app->reportRepository->updateRelevantReports($reportId, $report->getEntityType(), $report->getEntityId(), ['statusComment' => $relevantText, 'status' => ReportStatus::RESOLVED]);

            $this->flashMessage('Closed report #' . $reportId . '.');
            
            if(!empty($idReleveantReports)) {
                $this->flashMessage('Closed relevant reports: #' . implode(', #', $idReleveantReports));
            }

            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:FeedbackReports', 'action' => 'resolutionForm', 'isSubmit' => '1', 'reportId' => $reportId])
                ->addTextArea('comment', 'Resolution comment:', null, true)
                ->addSubmit('Close report')
            ;
        
            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderResolutionForm() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }
}

?>