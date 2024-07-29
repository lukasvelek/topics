<?php

namespace App\Modules\UserModule;

use App\Constants\ReportCategory;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;

class UsersPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('UsersPresenter', 'Users');
    }

    public function handleProfile() {
        global $app;

        $userId = $this->httpGet('userId');
        $user = $app->userRepository->getUserById($userId);

        $this->saveToPresenterCache('user', $user);

        $postCount = $app->postRepository->getPostCountForUserId($userId);

        $this->saveToPresenterCache('postCount', $postCount);

        $reportLink = '<a class="post-data-link" href="?page=UserModule:Users&action=reportUser&userId=' . $userId . '">Report</a>';

        $this->saveToPresenterCache('reportLink', $reportLink);

        /** ACTION HISTORY */
        $actionHistory = null;
        $app->logger->stopwatch(function() use (&$actionHistory, $app) {
            $actionHistory = $app->contentManager->getUserActionHistory($app->currentUser->getId(), 10);
        }, 'App\\Managers\\ContentManager::getUserActionHistory');

        $this->saveToPresenterCache('actionHistory', $actionHistory);
    }

    public function renderProfile() {
        $user = $this->loadFromPresenterCache('user');
        $postCount = $this->loadFromPresenterCache('postCount');
        $reportLink = $this->loadFromPresenterCache('reportLink');
        $actionHistory = $this->loadFromPresenterCache('actionHistory');

        $this->template->username = $user->getUsername();
        $this->template->post_count = $postCount;
        $this->template->first_login_date = DateTimeFormatHelper::formatDateToUserFriendly($user->getDateCreated());
        $this->template->report_link = $reportLink;
        $this->template->action_history = $actionHistory;
    }

    public function handleReportUser(?FormResponse $fr = null) {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
            $authorId = $app->currentUser->getId();
            
            try {
                $app->reportRepository->beginTransaction();

                $app->reportRepository->createUserReport($authorId, $userId, $category, $description);

                $app->reportRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('User reported.', 'success');
            } catch(AException $e) {
                $app->reportRepository->rollback();

                $this->flashMessage('User could not be reported. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $userId]);
        } else {
            $this->saveToPresenterCache('user', $user);

            $categories = ReportCategory::getArray();
            $categoryArray = [];
            foreach($categories as $k => $v) {
                $categoryArray[] = [
                    'value' => $k,
                    'text' => $v
                ];
            }

            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'UserModule:Users', 'action' => 'reportUser', 'isSubmit' => '1', 'userId' => $userId])
                ->addSelect('category', 'Category:', $categoryArray, true)
                ->addTextArea('description', 'Additional notes:', null, true)
                ->addSubmit('Send')
                ;

            $this->saveToPresenterCache('form', $fb);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['userId' => $userId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderReportUser() {
        $user = $this->loadFromPresenterCache('user');
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->username = $user->getUsername();
        $this->template->form = $form;
        $this->template->links = $links;
    }
}

?>