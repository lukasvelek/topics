<?php

namespace App\Modules\UserModule;

use App\Constants\ReportCategory;
use App\Core\AjaxRequestBuilder;
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
        $userId = $this->httpGet('userId');
        $user = $this->app->userRepository->getUserById($userId);

        $this->saveToPresenterCache('user', $user);

        $postCount = $this->app->postRepository->getPostCountForUserId($userId);

        $this->saveToPresenterCache('postCount', $postCount);

        $reportLink = '<a class="post-data-link" href="?page=UserModule:Users&action=reportUser&userId=' . $userId . '">Report</a>';

        $this->saveToPresenterCache('reportLink', $reportLink);
        
        $followerCount = $this->app->userFollowingManager->getFollowerCount($userId);
        $this->saveToPresenterCache('followerCount', $followerCount);

        $followingCount = $this->app->userFollowingManager->getFollowingCount($userId);
        $this->saveToPresenterCache('followingCount', $followingCount);

        $followLink = '';

        if($this->getUserId() != $userId) {
            if($this->app->userFollowingManager->canFollowUser($this->getUserId(), $userId)) {
                $followLink = LinkBuilder::createSimpleLink('Follow', $this->createURL('followUser', ['userId' => $userId]), 'post-data-link');
            } else {
                $followLink = LinkBuilder::createSimpleLink('Unfollow', $this->createURL('unfollowUser', ['userId' => $userId]), 'post-data-link');
            }
        }

        $this->saveToPresenterCache('followLink', $followLink);

        $manageFollowersLink = 'Followers';
        if($this->getUserId() == $userId) {
            $manageFollowersLink = LinkBuilder::createSimpleLinkObject('Followers', $this->createURL('manageFollowers'), 'post-data-link');
            $manageFollowersLink->setTitle('Manage followers');
        }
        $this->saveToPresenterCache('manageFollowersLink', $manageFollowersLink);

        $manageFollowingLink = 'Following';
        if($this->getUserId() == $userId) {
            $manageFollowingLink = LinkBuilder::createSimpleLinkObject('Following', $this->createURL('manageFollowing'), 'post-data-link');
            $manageFollowingLink->setTitle('Manage followings');
        }
        $this->saveToPresenterCache('manageFollowingLink', $manageFollowingLink);

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setAction($this, 'loadUserActionHistory')
            ->setHeader(['userId' => '_userId'])
            ->setFunctionName('loadUserActionHistory')
            ->setFunctionArguments(['_userId'])
            ->updateHTMLElement('action-history', 'actionHistory')
        ;

        $this->addScript($arb->build());
        $this->addScript('loadUserActionHistory("' . $userId . '")');
    }

    public function renderProfile() {
        $user = $this->loadFromPresenterCache('user');
        $postCount = $this->loadFromPresenterCache('postCount');
        $reportLink = $this->loadFromPresenterCache('reportLink');
        $followerCount = $this->loadFromPresenterCache('followerCount');
        $followingCount = $this->loadFromPresenterCache('followingCount');
        $followLink = $this->loadFromPresenterCache('followLink');
        $manageFollowersLink = $this->loadFromPresenterCache('manageFollowersLink');
        $manageFollowingLink = $this->loadFromPresenterCache('manageFollowingLink');

        $this->template->username = $user->getUsername();
        $this->template->post_count = $postCount;
        $this->template->first_login_date = DateTimeFormatHelper::formatDateToUserFriendly($user->getDateCreated());
        $this->template->report_link = $reportLink;
        $this->template->followers_count = $followerCount;
        $this->template->following_count = $followingCount;
        $this->template->follow_link = $followLink;
        $this->template->manage_followers_link = $manageFollowersLink;
        $this->template->manage_following_link = $manageFollowingLink;
    }

    public function actionLoadUserActionHistory() {
        $userId = $this->httpGet('userId');

        $actionHistory = $this->app->contentManager->getUserActionHistory($userId, 10);

        return ['actionHistory' => $actionHistory];
    }

    public function handleManageFollowers() {
        $totalFollowers = $this->app->userFollowingManager->getFollowerCount($this->getUserId());

        $arb = new AjaxRequestBuilder();
        $arb->setAction($this, 'getFollowersList')
            ->setMethod()
            ->setHeader(['offset' => '_offset'])
            ->setFunctionName('getFollowersList')
            ->setFunctionArguments(['_offset'])
            ->updateHTMLElement('followers-list', 'list', true)
            ->updateHTMLElement('followers-list-load-more-link', 'loadMoreLink', false);

        $this->addScript($arb);
        $this->addScript('getFollowersList(0)');

        $backLink = LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['userId' => $this->getUserId()]), 'post-data-link');
        
        $this->saveToPresenterCache('username', $this->getUser()?->getUsername());
        $this->saveToPresenterCache('totalFollowers', $totalFollowers);
        $this->saveToPresenterCache('backLink', $backLink);
    }

    public function renderManageFollowers() {
        $followers = $this->loadFromPresenterCache('followers');
        $username = $this->loadFromPresenterCache('username');
        $totalFollowers = $this->loadFromPresenterCache('totalFollowers');
        $backLink = $this->loadFromPresenterCache('backLink');

        $this->template->followers = $followers;
        $this->template->username = $username;
        $this->template->total_followers = $totalFollowers;
        $this->template->back_link = $backLink;
    }

    public function actionGetFollowersList() {
        $offset = $this->httpGet('offset');
        $limit = 10;

        $followers = $this->app->userFollowingManager->getFollowersForUserWithOffset($this->getUserId(), $limit, $offset);

        $totalFollowers = $this->app->userFollowingManager->getFollowerCount($this->getUserId());

        $codeArray = [];
        foreach($followers as $follower) {
            $user = $this->app->userRepository->getUserById($follower->getAuthorId());

            $codeArray[] = '
                <div class="row">
                    <div class="col-md col-lg" id="center">
                        ' . LinkBuilder::createSimpleLink($user->getUsername(), $this->createURL('profile', ['userId' => $user->getId()]), 'user-list-link') . '
                    </div>
                </div>
            ';
        }

        $loadMoreLink = '';
        if(($limit * ($offset + 1)) < $totalFollowers) {
            $loadMoreLink = '<br><button type="button" onclick="getFollowersList(' . ($offset + $limit) . ')">Load more</button>';
        }

        return ['list' => implode('<br>', $codeArray), 'loadMoreLink' => $loadMoreLink];
    }

    public function handleManageFollowing() {
        $totalFollowers = $this->app->userFollowingManager->getFollowingCount($this->getUserId());

        $arb = new AjaxRequestBuilder();
        $arb->setAction($this, 'getFollowsList')
            ->setMethod()
            ->setHeader(['offset' => '_offset'])
            ->setFunctionName('getFollowsList')
            ->setFunctionArguments(['_offset'])
            ->updateHTMLElement('follows-list', 'list', true)
            ->updateHTMLElement('follows-list-load-more-link', 'loadMoreLink', false);

        $this->addScript($arb);
        $this->addScript('getFollowsList(0)');

        $backLink = LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['userId' => $this->getUserId()]), 'post-data-link');
        
        $this->saveToPresenterCache('username', $this->getUser()?->getUsername());
        $this->saveToPresenterCache('totalFollows', $totalFollowers);
        $this->saveToPresenterCache('backLink', $backLink);
    }

    public function renderManageFollowing() {
        $follows = $this->loadFromPresenterCache('followers');
        $username = $this->loadFromPresenterCache('username');
        $totalFollows = $this->loadFromPresenterCache('totalFollows');
        $backLink = $this->loadFromPresenterCache('backLink');

        $this->template->follows = $follows;
        $this->template->username = $username;
        $this->template->total_follows = $totalFollows;
        $this->template->back_link = $backLink;
    }

    public function actionGetFollowsList() {
        $offset = $this->httpGet('offset');
        $limit = 10;

        $followers = $this->app->userFollowingManager->getFollowsForUserWithOffset($this->getUserId(), $limit, $offset);

        $totalFollows = $this->app->userFollowingManager->getFollowingCount($this->getUserId());

        $codeArray = [];
        foreach($followers as $follower) {
            $user = $this->app->userRepository->getUserById($follower->getUserId());

            $codeArray[] = '
                <div class="row">
                    <div class="col-md col-lg" id="center">
                        ' . LinkBuilder::createSimpleLink($user->getUsername(), $this->createURL('profile', ['userId' => $user->getId()]), 'user-list-link') . '
                    </div>
                </div>
            ';
        }

        $loadMoreLink = '';
        if(($limit * ($offset + 1)) < $totalFollows) {
            $loadMoreLink = '<br><button type="button" onclick="getFollowsList(' . ($offset + $limit) . ')">Load more</button>';
        }

        $result = implode('<br>', $codeArray);

        if($offset > 0) {
            $result = '<br>' . $result;
        }

        return ['list' => $result, 'loadMoreLink' => $loadMoreLink];
    }

    public function handleFollowUser() {
        $userId = $this->httpGet('userId', true);

        try {
            $this->app->userFollowingRepository->beginTransaction();

            $this->app->userFollowingManager->followUser($this->getUserId(), $userId);

            $this->app->userFollowingRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('User followed.', 'success');
        } catch(AException $e) {
            $this->app->userFollowingRepository->rollback();

            $this->flashMessage('Could not follow user. Reason: '. $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('profile', ['userId' => $userId]));
    }

    public function handleUnfollowUser() {
        $userId = $this->httpGet('userId', true);

        try {
            $this->app->userFollowingRepository->beginTransaction();

            $this->app->userFollowingManager->unfollowUser($this->getUserId(), $userId);

            $this->app->userFollowingRepository->commit($this->getUserId(), __METHOD__);

            $this->flashMessage('User unfollowed.', 'success');
        } catch(AException $e) {
            $this->app->userFollowingRepository->rollback();

            $this->flashMessage('Could not unfollow user. Reason: '. $e->getMessage(), 'error');
        }

        $this->redirect($this->createURL('profile', ['userId' => $userId]));
    }

    public function handleReportUser(?FormResponse $fr = null) {
        $userId = $this->httpGet('userId', true);
        $user = $this->app->userRepository->getUserById($userId);

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
            $authorId = $this->getUserId();
            
            try {
                $this->app->reportRepository->beginTransaction();

                $this->app->reportRepository->createUserReport($authorId, $userId, $category, $description);

                $this->app->reportRepository->commit($this->getUserId(), __METHOD__);

                $this->flashMessage('User reported.', 'success');
            } catch(AException $e) {
                $this->app->reportRepository->rollback();

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