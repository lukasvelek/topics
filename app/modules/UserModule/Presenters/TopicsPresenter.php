<?php

namespace App\Modules\UserModule;

use App\Constants\ReportCategory;
use App\Core\CacheManager;
use App\Exceptions\AException;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;

class TopicsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('TopicsPresenter', 'Topics');
    }

    public function handleProfile() {
        global $app;

        $topicId = $this->httpGet('topicId');

        // topic info
        $topic = $app->topicRepository->getTopicById($topicId);

        $this->saveToPresenterCache('topic', $topic);

        // posts
        $this->saveToPresenterCache('posts', '<script type="text/javascript">loadPostsForTopic(' . $topicId .', 10, 0, ' . $app->currentUser->getId() . ')</script><div id="post-list"></div><div id="post-list-link"></div><br>');

        // topic data
        $manager = $app->userRepository->getUserById($topic->getManagerId());

        $managerLink = '<a class="post-data-link" href="' . $app->composeURL(['page' => 'UserModule:Users', 'action' => 'profile', 'userId' => $manager->getId()]) . '">' . $manager->getUsername() . '</a>';

        $topicFollowers = $app->topicRepository->getFollowersForTopicId($topicId);
        $postCount = $app->postRepository->getPostCountForTopicId($topicId);

        $followLink = '<a class="post-data-link" href="?page=UserModule:Topics&action=follow&topicId=' . $topicId . '">Follow</a>';
        $unFollowLink = '<a class="post-data-link" href="?page=UserModule:Topics&action=unfollow&topicId=' . $topicId . '">Unfollow</a>';
        $followed = $app->topicRepository->checkFollow($app->currentUser->getId(), $topicId);
        $isManager = $app->currentUser->getId() == $topic->getManagerId();

        $reportLink = '<a class="post-data-link" href="?page=UserModule:Topics&action=reportForm&topicId=' . $topicId . '">Report topic</a>';

        $code = '
            <p class="post-data">Followers: ' . count($topicFollowers) . ' ' . ($followed ? ($isManager ? '' : $unFollowLink) : $followLink) . '</p>
            <p class="post-data">Manager: ' . $managerLink . '</p>
            <p class="post-data">Topic started on: ' . DateTimeFormatHelper::formatDateToUserFriendly($topic->getDateCreated()) . '</p>
            <p class="post-data">Posts: ' . $postCount . '</p>
            <p class="post-data">' . $reportLink . '</p>
        ';

        $this->saveToPresenterCache('topicData', $code);

        // new post form
        $fb = new FormBuilder();

        $fb ->setAction(['page' => 'UserModule:Topics', 'action' => 'newPost', 'topicId' => $topicId])
            ->addTextInput('title', 'Title:', null, true)
            ->addTextArea('text', 'Text:', null, true)
            ->addSubmit('Post')
        ;

        $this->saveToPresenterCache('newPostForm', $fb);
    }

    public function renderProfile() {
        $topic = $this->loadFromPresenterCache('topic');
        $posts = $this->loadFromPresenterCache('posts');
        $topicData = $this->loadFromPresenterCache('topicData');
        $fb = $this->loadFromPresenterCache('newPostForm');

        $this->template->topic_title = $topic->getTitle();
        $this->template->topic_description = $topic->getDescription();
        $this->template->latest_posts = $posts;
        $this->template->topic_data = $topicData;
        $this->template->new_post_form = $fb->render();
    }

    public function handleNewPost() {
        global $app;

        $title = $this->httpPost('title');
        $text = $this->httpPost('text');
        $userId = $app->currentUser->getId();
        $topicId = $this->httpGet('topicId');

        try {
            $app->postRepository->createNewPost($topicId, $userId, $title, $text);
        } catch (AException $e) {
            $this->flashMessage('Post could not be created. Error: ' . $e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        }

        $this->flashMessage('Post created.', 'success');
        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handleSearch() {
        global $app;

        $query = $this->httpGet('q');

        $topics = $app->topicRepository->searchTopics($query);

        $topicCode = '';
        if(!empty($topics)) {
            foreach($topics as $topic) {
                $topicLink = '<a class="post-title-link-smaller" href="?page=UserModule:Topics&action=profile&topicId=' . $topic->getId() . '">' . $topic->getTitle() . '</a>';

                $tmp = '
                    <div class="row">
                        <div class="col-md">
                            ' . $topicLink . '
                        </div>
                    </div>
                ';
                
                $topicCode .= $tmp;
            }
        } else {
            $tmp = '
                <div class="row">
                    <div class="col-md">
                        <p class="topic-title">No topics found. :(</p>
                        <p class="topic-title">But you can <a class="topic-title-link" href="?page=UserModule:Topics&action=form&title=' . $query . '">start a new one</a>!</p>
                    </div>
                </div>
            ';

            $topicCode = $tmp;
        }
        
        $this->saveToPresenterCache('topics', $topicCode);
    }

    public function renderSearch() {
        $this->template->search_data = $this->loadFromPresenterCache('topics');
    }

    public function handleForm() {
        global $app;

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            // process submitted form

            $title = $this->httpPost('title');
            $description = $this->httpPost('description');

            $topicId = null;

            try {
                $app->topicRepository->createNewTopic($app->currentUser->getId(), $title, $description);
                $topicId = $app->topicRepository->getLastTopicIdForManagerId($app->currentUser->getId());
                $app->topicRepository->followTopic($app->currentUser->getId(), $topicId);

                CacheManager::invalidateCache('topics');
            } catch(AException $e) {
                $this->flashMessage('Could not create a new topic. Reason: ' . $e->getMessage(), 'error');
                $app->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
            }

            $this->flashMessage('Topic \'' . $title . '\' created.', 'success');
            $app->redirect(['page' => 'UserModule:Topics&action=profile&topicId=' . $topicId]);
        } else {
            $title = $this->httpGet('title');

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'UserModule:Topics', 'action' => 'form', 'isSubmit' => '1'])
                ->addTextInput('title', 'Title:', $title, true)
                ->addTextArea('description', 'Description:', null, true)
                ->addSubmit('Create topic');

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderForm() {
        $this->template->form = $this->loadFromPresenterCache('form')->render();
    }

    public function handleFollow() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $topic = $app->topicRepository->getTopicById($topicId);

        if($app->topicRepository->followTopic($app->currentUser->getId(), $topicId) !== false) {
            $this->flashMessage('Topic \'' . $topic->getTitle() . '\' followed.', 'success');
        } else {
            $this->flashMessage('Could not follow topic \'' . $topic->getTitle() . '\'', 'error');
        }

        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handleUnfollow() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $topic = $app->topicRepository->getTopicById($topicId);

        if($app->topicRepository->unfollowTopic($app->currentUser->getId(), $topicId) !== false) {
            $this->flashMessage('Topic \'' . $topic->getTitle() . '\' unfollowed.', 'success');
        } else {
            $this->flashMessage('Could not unfollow topic \'' . $topic->getTitle() . '\'', 'error');
        }

        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handleFollowed() {
        global $app;

        $followedTopics = $app->topicRepository->getFollowedTopicIdsForUser($app->currentUser->getId());

        $code = [];

        if(!empty($followedTopics)) {
            foreach($followedTopics as $topicId) {
                $topic = $app->topicRepository->getTopicById($topicId);
    
                $code[] = '
                    <div class="row">
                        <div class="col-md">
                            <a class="post-title-link" href="?page=UserModule:Topics&action=profile&topicId=' . $topicId . '">' . $topic->getTitle() . '</a>
                        </div>
                    </div>
                    <hr>
                ';
            }
        } else {
            $code[] = '
                <div class="row">
                    <div class="col-md">
                        <p class="post-text">No data found.</p>
                    </div>
                </div>
            ';
        }

        $this->saveToPresenterCache('topics', implode('', $code));
    }

    public function renderFollowed() {
        $topics = $this->loadFromPresenterCache('topics');
        $this->template->topics = $topics;
    }

    public function handleDiscover() {
        global $app;

        $notFollowedTopics = $app->topicRepository->getNotFollowedTopics($app->currentUser->getId());

        $code = [];

        if(!empty($notFollowedTopics)) {
            foreach($notFollowedTopics as $topic) {
                $topicId = $topic->getId();
    
                $code[] = '
                    <div class="row">
                        <div class="col-md">
                            <a class="post-title-link" href="?page=UserModule:Topics&action=profile&topicId=' . $topicId . '">' . $topic->getTitle() . '</a>
                        </div>
                    </div>
                    <hr>
                ';
            }
        } else {
            $code[] = '
                <div class="row">
                    <div class="col-md">
                        <p class="post-text">No data found.</p>
                    </div>
                </div>
            ';
        }

        $this->saveToPresenterCache('topics', implode('', $code));
    }

    public function renderDiscover() {
        $topics = $this->loadFromPresenterCache('topics');
        $this->template->topics = $topics;
    }

    public function handleReportForm() {
        global $app;

        $topicId = $this->httpGet('topicId');
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $this->httpPost('category');
            $description = $this->httpPost('description');
            $userId = $app->currentUser->getId();

            $app->reportRepository->createTopicReport($userId, $topicId, $category, $description);

            $this->flashMessage('Topic reported.', 'success');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        } else {
            $topic = $app->topicRepository->getTopicById($topicId);
            $this->saveToPresenterCache('topic', $topic);

            $categories = ReportCategory::getArray();
            $categoryArray = [];
            foreach($categories as $k => $v) {
                $categoryArray[] = [
                    'value' => $k,
                    'text' => $v
                ];
            }

            $fb = new FormBuilder();
            $fb ->setAction(['page' => 'UserModule:Topics', 'action' => 'reportForm', 'isSubmit' => '1', 'topicId' => $topicId])
                ->addSelect('category', 'Category:', $categoryArray, true)
                ->addTextArea('description', 'Additional notes:', null, true)
                ->addSubmit('Send')
                ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderReportForm() {
        $topic = $this->loadFromPresenterCache('topic');
        $form = $this->loadFromPresenterCache('form');

        $this->template->topic_title = $topic->getTitle();
        $this->template->form = $form->render();
    }
}

?>