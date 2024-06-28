<?php

namespace App\Modules\UserModule;

use App\Constants\ReportCategory;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;

class UsersPresenter extends APresenter {
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
    }

    public function renderProfile() {
        $user = $this->loadFromPresenterCache('user');
        $postCount = $this->loadFromPresenterCache('postCount');
        $reportLink = $this->loadFromPresenterCache('reportLink');

        $this->template->username = $user->getUsername();
        $this->template->post_count = $postCount;
        $this->template->first_login_date = DateTimeFormatHelper::formatDateToUserFriendly($user->getDateCreated());
        $this->template->report_link = $reportLink;
    }

    public function handleReportUser(?FormResponse $fr = null) {
        global $app;

        $userId = $this->httpGet('userId', true);
        $user = $app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
            $authorId = $app->currentUser->getId();
            
            $app->reportRepository->createUserReport($authorId, $userId, $category, $description);

            $this->flashMessage('User reported.', 'success');
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
        }
    }

    public function renderReportUser() {
        $user = $this->loadFromPresenterCache('user');
        $form = $this->loadFromPresenterCache('form');

        $this->template->username = $user->getUsername();
        $this->template->form = $form;
    }
}

?>