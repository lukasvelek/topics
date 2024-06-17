<?php

namespace App\Modules\AdminModule;

use App\Components\Sidebar\Sidebar;
use App\Constants\ReportCategory;
use App\Constants\ReportEntityType;
use App\Constants\ReportStatus;
use App\Constants\UserProsecutionType;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class FeedbackReportsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('FeedbackReportsPresenter', 'Reports');

        $this->addBeforeRenderCallback(function() {
            $this->template->sidebar = $this->createSidebar();
        });
    }

    private function createSidebar() {
        $sb = new Sidebar();
        $sb->addLink('Dashboard', ['page' => 'AdminModule:Feedback', 'action' => 'dashboard']);
        $sb->addLink('Suggestions', ['page' => 'AdminModule:FeedbackSuggestions', 'action' => 'list']);
        $sb->addLink('Reports', ['page' => 'AdminModule:FeedbackReports', 'action' => 'list'], true);

        return $sb->render();
    }

    public function handleList() {
        global $app;

        $filterType = $this->httpGet('filterType') ?? 'null';
        $filterKey = $this->httpGet('filterKey') ?? 'null';

        $this->saveToPresenterCache('list', '<script type="text/javascript">loadReports(10, 0, ' . $app->currentUser->getId() . ', \'' . $filterType . '\', \'' . $filterKey . '\')</script><div id="report-list"></div><div id="report-list-link"></div><br>');
    }

    public function renderList() {
        $list = $this->loadFromPresenterCache('list');

        $this->template->reports = $list;
    }

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
                            ((strtotime($userProsecution->getStartDate()) > time()) && (strtotime($userProsecution->getEndDate() > time()) && ($userProsecution->getType() == UserProsecutionType::BAN)))) {
                            $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:ManageUsers&action=banUser&userId=' . $report->getEntityId() . '&reportId=' . $report->getId() . '">Ban user</a>';
                        } else {
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
            $adminLinks[] = '<a class="post-data-link" href="?page=AdminModule:FeedbackReports&action=reopen&reportId=' . $report->getId() . '">Reopen</a>';
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

    public function handleResolutionForm() {
        global $app;

        $reportId = $this->httpGet('reportId', true);
        $report = $app->reportRepository->getReportById($reportId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $comment = $this->httpPost('comment');
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

        $this->template->form = $form->render();
    }
}

?>