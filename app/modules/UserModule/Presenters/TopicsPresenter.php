<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Constants\ReportCategory;
use App\Constants\TopicMemberRole;
use App\Core\AjaxRequestBuilder;
use App\Core\CacheManager;
use App\Exceptions\AException;
use App\Helpers\BannedWordsHelper;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;

class TopicsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('TopicsPresenter', 'Topics');
    }

    public function handleProfile() {
        global $app;

        $bwh = new BannedWordsHelper($app->contentRegulationRepository);

        $topicId = $this->httpGet('topicId');

        $topic = $app->topicRepository->getTopicById($topicId);

        if($topic->isDeleted() && !$app->visibilityAuthorizator->canViewDeletedTopic($app->currentUser->getId())) {
            $this->flashMessage('This topic does not exist.', 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
        }

        // topic info
        $this->saveToPresenterCache('topic', $topic);

        $topicName = $bwh->checkText($topic->getTitle());
        $this->saveToPresenterCache('topicName', $topicName);

        $topicDescription = $bwh->checkText($topic->getDescription());
        $this->saveToPresenterCache('topicDescription', $topicDescription);

        // posts
        $postLimit = 10;
        $arb = new AjaxRequestBuilder();

        $arb->setURL(['page' => 'UserModule:Topics', 'action' => 'loadPostsForTopic'])
            ->setMethod('GET')
            ->setHeader(['limit' => '_limit', 'offset' => '_offset', 'topicId' => '_topicId'])
            ->setFunctionName('loadPostsForTopic')
            ->setFunctionArguments(['_limit', '_offset', '_topicId'])
            ->updateHTMLElement('latest-posts', 'posts', true)
            ->updateHTMLElement('load-more-link', 'loadMoreLink')
        ;

        $this->addScript($arb->build());
        $this->addScript('loadPostsForTopic(' . $postLimit . ', 0, ' . $topicId . ')');

        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'UserModule:Topics', 'action' => 'likePost'])
            ->setMethod('GET')
            ->setHeader(['postId' => '_postId', 'userId' => '_userId', 'toLike' => '_toLike'])
            ->setFunctionName('likePost')
            ->setFunctionArguments(['_postId', '_userId', '_toLike'])
            ->updateHTMLElementRaw('"#post-" + _postId + "-likes"', 'postLikes')
            ->updateHTMLElementRaw('"#post-" + _postId + "-link"', 'postLink')
        ;

        $this->addScript($arb->build());

        $topicFollowers = $app->topicRepository->getFollowersForTopicId($topicId);
        $postCount = $app->postRepository->getPostCountForTopicId($topicId, !$topic->isDeleted());

        $followLink = '<a class="post-data-link" href="?page=UserModule:Topics&action=follow&topicId=' . $topicId . '">Follow</a>';
        $unFollowLink = '<a class="post-data-link" href="?page=UserModule:Topics&action=unfollow&topicId=' . $topicId . '">Unfollow</a>';
        $followed = $app->topicRepository->checkFollow($app->currentUser->getId(), $topicId);
        $finalFollowLink = '';

        if(!$topic->isDeleted()) {
            $finalFollowLink = ($followed ? $unFollowLink : $followLink);
        }

        $reportLink = '';

        if(!$topic->isDeleted() && $app->actionAuthorizator->canReportTopic($app->currentUser->getId(), $topicId)) {
            $reportLink = '<a class="post-data-link" href="?page=UserModule:Topics&action=reportForm&topicId=' . $topicId . '">Report topic</a>';
        }

        $deleteLink = '';

        if($app->actionAuthorizator->canDeleteTopic($app->currentUser->getId()) && !$topic->isDeleted()) {
            $deleteLink = '<p class="post-data"><a class="post-data-link" href="?page=UserModule:Topics&action=deleteTopic&topicId=' . $topicId . '">Delete topic</a></p>';
        } else if($topic->isDeleted()) {
            $deleteLink = '<p class="post-data">Topic deleted</p>';
        }

        $roleManagementLink = '';

        if($app->actionAuthorizator->canManageTopicRoles($topicId, $app->currentUser->getId())) {
            $roleManagementLink = '<p class="post-data"><a class="post-data-link" href="?page=UserModule:TopicManagement&action=manageRoles&topicId=' . $topicId . '">Manage roles</a>';
        }

        $code = '
            <p class="post-data">Followers: ' . count($topicFollowers) . ' ' . $finalFollowLink . '</p>
            <p class="post-data">Topic started on: ' . DateTimeFormatHelper::formatDateToUserFriendly($topic->getDateCreated()) . '</p>
            <p class="post-data">Posts: ' . $postCount . '</p>
            <p class="post-data">' . $reportLink . '</p>
            ' . $deleteLink . '
            ' . $roleManagementLink . '
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

        if($topic->isDeleted()) {
            $this->addExternalScript('js/Reducer.js');
            $this->addScript('reduceTopicProfile()');
        }
    }

    public function actionLikePost() {
        global $app;

        $userId = $this->httpGet('userId');
        $postId = $this->httpGet('postId');
        $toLike = $this->httpGet('toLike');

        $liked = false;

        if($toLike == 'true') {
            $app->postRepository->likePost($userId, $postId);
            $liked = true;
        } else {
            $app->postRepository->unlikePost($userId, $postId);
        }

        $likes = $app->postRepository->getLikes($postId);

        $this->ajaxSendResponse(['postLink' => PostLister::createLikeLink($userId, $postId, $liked), 'postLikes' => $likes]);
    }

    public function actionLoadPostsForTopic() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $limit = $this->httpGet('limit');
        $offset = $this->httpGet('offset');

        $topic = $app->topicRepository->getTopicById($topicId);

        $posts = $app->postRepository->getLatestPostsForTopicId($topicId, $limit, $offset, !$topic->isDeleted());
        $postCount = $app->postRepository->getPostCountForTopicId($topicId, !$topic->isDeleted());

        if(empty($posts)) {
            return $this->ajaxSendResponse(['posts' => '<p class="post-text" id="center">No posts found</p>', 'loadMoreLink' => '']);
        }

        $code = [];

        $bwh = new BannedWordsHelper($app->contentRegulationRepository);

        foreach($posts as $post) {
            $author = $app->userRepository->getUserById($post->getAuthorId());
            $userProfileLink = $app->topicMembershipManager->createUserProfileLinkWithRole($author, $post->getTopicId());
    
            $title = $bwh->checkText($post->getTitle());
    
            $postLink = '<a class="post-title-link" href="?page=UserModule:Posts&action=profile&postId=' . $post->getId() . '">' . $title . '</a>';

            $liked = $app->postRepository->checkLike($app->currentUser->getId(), $post->getId());
            $likeLink = '<a class="post-like" style="cursor: pointer" href="#post-' . $post->getId() . '-link" onclick="likePost(' . $post->getId() . ', ' . $app->currentUser->getId() . ', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';
    
            $shortenedText = $bwh->checkText($post->getShortenedText(100));
    
            $tmp = [
                '<div class="row" id="post-' . $post->getId() . '">',
                '<div class="col-md">',
                '<p class="post-title">' . $postLink . '</p>',
                '<hr>',
                '<p class="post-text">' . $shortenedText . '</p>',
                '<hr>',
                '<p class="post-data">Likes: <span id="post-' . $post->getId() . '-likes">' . $post->getLikes() . '</span> <span id="post-' . $post->getId() . '-link">' . $likeLink . '</span>',
                ' | Author: ' . $userProfileLink . ' | Date: ' . DateTimeFormatHelper::formatDateToUserFriendly($post->getDateCreated()) . '</p>',
                '</div></div><br>'
            ];
    
            $code[] = implode('', $tmp);
        }

        if(($offset + $limit) >= $postCount) {
            $loadMoreLink = '';
        } else {
            $loadMoreLink = '<a class="post-data-link" onclick="loadPostsForTopic(' . $limit . ',' . ($offset + $limit) . ', ' . $topicId . ')" href="#">Load more</a>';
        }

        $this->ajaxSendResponse(['posts' => implode('', $code), 'loadMoreLink' => $loadMoreLink]);
    }

    public function renderProfile() {
        $posts = $this->loadFromPresenterCache('posts');
        $topicData = $this->loadFromPresenterCache('topicData');
        $fb = $this->loadFromPresenterCache('newPostForm');
        $topicName = $this->loadFromPresenterCache('topicName');
        $topicDescription = $this->loadFromPresenterCache('topicDescription');

        $this->template->topic_title = $topicName;
        $this->template->topic_description = $topicDescription;
        $this->template->latest_posts = $posts;
        $this->template->topic_data = $topicData;
        $this->template->new_post_form = $fb;
    }

    public function handleNewPost(?FormResponse $fr = null) {
        global $app;

        $title = $fr->title;
        $text = $fr->text;
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

    public function handleForm(?FormResponse $fr = null) {
        global $app;

        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            // process submitted form

            $title = $fr->title;
            $description = $fr->description;

            $topicId = null;

            try {
                $app->topicRepository->createNewTopic($title, $description);
                $topicId = $app->topicRepository->getLastTopicIdForTitle($title);
                $app->topicMembershipManager->followTopic($topicId, $app->currentUser->getId());
                $app->topicMembershipManager->changeRole($topicId, $app->currentUser->getId(), $app->currentUser->getId(), TopicMemberRole::OWNER);

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
        $this->template->form = $this->loadFromPresenterCache('form');
    }

    public function handleFollow() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $topic = $app->topicRepository->getTopicById($topicId);

        $ok = false;

        $app->topicRepository->beginTransaction();

        try {
            $app->topicMembershipManager->followTopic($topicId, $app->currentUser->getId());
            $ok = true;
        } catch(AException $e) {
            $app->topicRepository->rollback();
            $this->flashMessage('Could not follow topic \'' . $topic->getTitle() . '\'. Reason: ' . $e->getMessage(), 'error');
        }

        if($ok) {
            $app->topicRepository->commit();
            $this->flashMessage('Topic \'' . $topic->getTitle() . '\' followed.', 'success');
        }

        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handleUnfollow() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $topic = $app->topicRepository->getTopicById($topicId);

        $ok = false;

        $app->topicRepository->beginTransaction();

        try {
            $app->topicMembershipManager->unfollowTopic($topicId, $app->currentUser->getId());
            $ok = true;
        } catch(AException $e) {
            $app->topicRepository->rollback();
            $this->flashMessage('Could not unfollow topic \'' . $topic->getTitle() . '\'. Reason: ' . $e->getMessage(), 'error');
        }

        if($ok) {
            $app->topicRepository->commit();
            $this->flashMessage('Topic \'' . $topic->getTitle() . '\' unfollowed.', 'success');
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

    public function handleReportForm(?FormResponse $fr = null) {
        global $app;

        $topicId = $this->httpGet('topicId');
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
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
        $this->template->form = $form;
    }

    public function handleDeleteTopic(?FormResponse $fr = null) {
        global $app;

        $topicId = $this->httpGet('topicId');

        if($this->httpGet('isSubmit') == '1') {
            $app->contentManager->deleteTopic($topicId);

            $this->flashMessage('Topic #' . $topicId . ' has been deleted.', 'success');
            $this->redirect(['action' => 'profile', 'topicId' => $topicId]);
        } else {
            $fb = new FormBuilder();
            
            $fb ->setAction(['page' => 'UserModule:Topics', 'action' => 'deleteTopic', 'isSubmit' => '1', 'topicId' => $topicId])
                ->addSubmit('Delete topic')
                ->addButton('&larr; Go back', 'location.href = \'?page=UserModule:Topics&action=profile&topicId=' . $topicId . '\';')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderDeleteTopic() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }
}

?>