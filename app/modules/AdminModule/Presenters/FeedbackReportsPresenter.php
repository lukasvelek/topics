<?php

namespace App\Modules\AdminModule;

use App\Constants\ReportCategory;
use App\Constants\ReportEntityType;
use App\Constants\ReportStatus;
use App\Constants\UserProsecutionType;
use App\Core\AjaxRequestBuilder;
use App\Core\DB\DatabaseRow;
use App\Core\Http\HttpRequest;
use App\Entities\ReportEntity;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Helpers\GridHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder2\Cell;
use App\UI\GridBuilder2\Row;
use App\UI\HTML\HTML;
use App\UI\LinkBuilder;

class FeedbackReportsPresenter extends AAdminPresenter {
    public function __construct() {
        parent::__construct('FeedbackReportsPresenter', 'Reports');
    }

    public function startup() {
        parent::startup();

        if(!$this->app->sidebarAuthorizator->canManageReports($this->getUserId())) {
            $this->flashMessage('You are not authorized to visit this section.');
            $this->redirect(['page' => 'AdminModule:Feedback', 'action' => 'dashboard']);
        }
    }

    public function createComponentGrid(HttpRequest $request) {
        $grid = $this->getGridBuilder();

        $grid->createDataSourceFromQueryBuilder($this->app->reportRepository->composeQueryForOpenReports(), 'reportId');
        $grid->setGridName(GridHelper::GRID_REPORTS);
        
        $usersInReports = $this->app->reportRepository->getUsersInReports();
        $userEntitiesInReports = [];
        foreach($usersInReports as $userId) {
            try {
                $user = $this->app->userManager->getUserById($userId);

                $userEntitiesInReports[$userId] = $user->getUsername();
            } catch(AException $e) {
                continue;
            }
        }

        $col = $grid->addColumnText('title', 'Title');
        $col->onRenderColumn[] = function(DatabaseRow $row, Row $_row, Cell $cell, HTML $html, mixed $value) {
            return ReportEntityType::toString($row->entityType) . ' report';
        };

        $grid->addColumnEnum('category', 'Category', ReportCategory::class);
        $grid->addColumnEnum('status', 'Status', ReportStatus::class);
        $grid->addColumnUser('userId', 'User');

        $grid->addFilter('category', null, ReportCategory::getAll());
        $grid->addFilter('status', null, ReportStatus::getAll());

        return $grid;
    }

    public function handleList() {}

    public function renderList() {}

    public function handleProfile() {
        $reportId = $this->httpGet('reportId', true);
        $report = $this->app->reportRepository->getReportById($reportId);

        $this->saveToPresenterCache('report', $report);

        try {
            $author = $this->app->userManager->getUserById($report->getUserId());
        } catch(AException $e) {
            $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'AdminModule:Home', 'action' => 'dashboard']);
        }
        $authorLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $report->getUserId() . '">' . $author->getUsername() . '</a>';
        $entityTypeText = ReportEntityType::toString($report->getEntityType());
        $entityLink = '<a class="post-data-link" href="?page=UserModule:';

        switch($report->getEntityType()) {
            case ReportEntityType::COMMENT:
                $comment = $this->app->postCommentRepository->getCommentById($report->getEntityId());
                $tmp = '';
                try {
                    $tmp = 'post';
                    $post = $this->app->postManager->getPostById($this->getUserId(), $comment->getPostId());

                    $tmp = 'user';
                    $author = $this->app->userManager->getUserById($comment->getAuthorId());
                } catch(AException $e) {
                    $this->flashMessage('Could not find ' . $tmp . '. Reason: ' . $e->getMessage(), 'error');
                    $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);
                }
                $entityLink .= 'Posts&action=profile&postId=' . $comment->getPostId() . '">Comment on post \'' . $post->getTitle() . '\' from user \'' . $author->getUsername() . '\' created on \'' . DateTimeFormatHelper::formatDateToUserFriendly($comment->getDateCreated()) .'\'</a>';
                break;

            case ReportEntityType::POST:
                try {
                    $post = $this->app->postManager->getPostById($this->getUserId(), $report->getEntityId());
                } catch(AException $e) {
                    $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
                    $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);
                }
                $entityLink .= 'Posts&action=profile&postId=' . $post->getId() . '">' . $post->getTitle() . '</a>';
                break;

            case ReportEntityType::TOPIC:
                try {
                    $topic = $this->app->topicManager->getTopicById($report->getEntityId(), $this->getUserId());
                } catch(AException $e) {
                    $this->flashMessage('Could not find topic. Reason: ' . $e->getMessage(), 'error');
                    $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);
                }
                $entityLink .= 'Topics&action=profile&topicId=' . $topic->getId() . '">' . $topic->getTitle() . '</a>';
                break;

            case ReportEntityType::USER:
                try {
                    $reportUser = $this->app->userManager->getUserById($report->getEntityId());
                } catch(AException $e) {
                    $this->flashMessage('Could not find user. Reason: ' . $e->getMessage(), 'error');
                    $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);
                }
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
            $adminLinks[] = LinkBuilder::createSimpleLink('Create a resolution', $this->createURL('resolutionForm', ['reportId' => $report->getId()]), 'post-data-link');

            switch($report->getEntityType()) {
                case ReportEntityType::COMMENT:
                    $comment = $this->app->postCommentRepository->getCommentById($report->getEntityId());

                    if($comment->isDeleted() !== true) {
                        $adminLinks[] = LinkBuilder::createSimpleLink('Delete comment', ['page' => 'AdminModule:ManagePosts', 'action' => 'deleteComment', 'commentId' => $report->getEntityId(), 'reportId' => $report->getId(), 'isFeedback' => '1'], 'post-data-link');
                    }

                    break;

                case ReportEntityType::USER:
                    $userProsecution = $this->app->userProsecutionRepository->getLastProsecutionForUserId($report->getEntityId());

                    if($userProsecution !== null) {
                        if($userProsecution->getType() == UserProsecutionType::PERMA_BAN) break;

                        if( $userProsecution->getType() == UserProsecutionType::WARNING ||
                            ((strtotime($userProsecution->getEndDate()) < time() && ($userProsecution->getType() == UserProsecutionType::BAN)))) {
                            $adminLinks[] = LinkBuilder::createSimpleLink('Ban user', ['page' => 'AdminModule:ManageUsers', 'action' => 'banUser', 'userId' => $report->getEntityId(), 'reportId' => $report->getId(), 'isFeedback' => '1'], 'post-data-link');
                        } else if($userProsecution->getType() != UserProsecutionType::BAN) {
                            $adminLinks[] = LinkBuilder::createSimpleLink('Warn user', ['page' => 'AdminModule:ManageUsers', 'action' => 'warnUser', 'userId' => $report->getEntityId(), 'reportId' => $report->getId(), 'isFeedback' => '1'], 'post-data-link');
                        }
                    } else {
                        $adminLinks[] = LinkBuilder::createSimpleLink('Ban user', ['page' => 'AdminModule:ManageUsers', 'action' => 'banUser', 'userId' => $report->getEntityId(), 'reportId' => $report->getId(), 'isFeedback' => '1'], 'post-data-link');
                        $adminLinks[] = LinkBuilder::createSimpleLink('Warn user', ['page' => 'AdminModule:ManageUsers', 'action' => 'warnUser', 'userId' => $report->getEntityId(), 'reportId' => $report->getId(), 'isFeedback' => '1'], 'post-data-link');
                    }

                    break;

                case ReportEntityType::POST:
                    try {
                        $post = $this->app->postManager->getPostById($this->getUserId(), $report->getEntityId());
                    } catch(AException $e) {
                        $this->flashMessage('Could not find post. Reason: ' . $e->getMessage(), 'error');
                        $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);
                    }

                    if($post->isDeleted() !== true) {
                        $adminLinks[] = LinkBuilder::createSimpleLink('Delete post', ['page' => 'AdminModule:ManagePosts', 'action' => 'deletePost', 'postId' => $report->getEntityId(), 'reportId' => $report->getId(), 'isFeedback' => '1'], 'post-data-link');
                    }

                    break;

                case ReportEntityType::TOPIC:
                    try {
                        $topic = $this->app->topicManager->getTopicById($report->getEntityId(), $this->getUserId());
                    } catch(AException $e) {
                        $this->flashMessage('Could not find topic. Reason: ' . $e->getMessage(), 'error');
                        $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'list']);
                    }

                    if($topic->isDeleted() !== true) {
                        $adminLinks[] = LinkBuilder::createSimpleLink('Delete topic', ['page' => 'AdminModule:ManageTopic', 'action' => 'deleteTopic', 'topicId' => $report->getEntityId(), 'reportId' => $report->getId(), 'isFeedback' => '1'], 'post-data-link');
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
        $reportId = $this->httpGet('reportId', true);
        $report = $this->app->reportRepository->getReportById($reportId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            /** SELECTED REPORT */
            $comment = $fr->comment;
            $userLink = '<a class="post-data-link" href="?page=UserModule:Users&action=profile&userId=' . $this->getUserId() . '">' . $this->getUser()->getUsername() . '</a>';
            $text = 'User ' . $userLink . ' closed this report with comment: ' . $comment;
            /** END OF SELECTED REPORT */
            
            /** RELEVANT REPORTS */
            $reportLink = '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=profile&reportId=' . $reportId . '">' . ReportEntityType::toString($report->getEntityType()) . ' report</a>';
            $relevantText = 'User ' . $userLink . ' closed report ' . $reportLink . ' that is relevant to this. Thus this report has been closed as well.';
            
            $relevantReports = $this->app->reportRepository->getRelevantReports($reportId);
            $idRelevantReports = [];
            foreach($relevantReports as $rr) {
                $idRelevantReports[] = $rr->getId();
            }
            /** END OF RELEVANT REPORTS */
            
            try {
                $this->app->reportRepository->beginTransaction();

                $this->app->reportRepository->updateReport($reportId, ['statusComment' => $text, 'status' => ReportStatus::RESOLVED]);
                
                if(!empty($idReleveantReports)) {
                    $this->app->reportRepository->updateRelevantReports($reportId, $report->getEntityType(), $report->getEntityId(), ['statusComment' => $relevantText, 'status' => ReportStatus::RESOLVED]);
                }
                
                $this->app->reportRepository->commit($this->getUserId(), __METHOD__);
                $this->flashMessage('Closed report #' . $reportId . '.');

                if(!empty($idRelevantReports)) {
                    $this->flashMessage('Closed relevant reports: #' . implode(', #', $idRelevantReports));
                }
            } catch(AException $e) {
                $this->app->reportRepository->rollback();

                if(empty($idRelevantReports)) {
                    $this->flashMessage('Could not close report. Reason: ' . $e->getMessage(), 'error');
                } else {
                    $this->flashMessage('Could not close report or any relevant report. Reason: ' . $e->getMessage(), 'error');
                }
            }

            $this->redirect(['page' => 'AdminModule:FeedbackReports', 'action' => 'profile', 'reportId' => $reportId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'AdminModule:FeedbackReports', 'action' => 'resolutionForm', 'isSubmit' => '1', 'reportId' => $reportId])
                ->addTextArea('comment', 'Resolution comment:', null, true)
                ->addSubmit('Close report', false, true)
            ;
        
            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['reportId' => $reportId]), 'post-data-link')
            ];
            
            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderResolutionForm() {
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->links = $links;
    }
}

?>