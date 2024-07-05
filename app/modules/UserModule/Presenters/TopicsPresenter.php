<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Constants\PostTags;
use App\Constants\ReportCategory;
use App\Constants\TopicMemberRole;
use App\Core\AjaxRequestBuilder;
use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Exceptions\AException;
use App\Helpers\BannedWordsHelper;
use App\Helpers\ColorHelper;
use App\Helpers\DateTimeFormatHelper;
use App\Modules\APresenter;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\LinkBuilder;

class TopicsPresenter extends APresenter {
    public function __construct() {
        parent::__construct('TopicsPresenter', 'Topics');
    }

    public function handleProfile() {
        global $app;

        $bwh = new BannedWordsHelper($app->contentRegulationRepository);

        $topicId = $this->httpGet('topicId');

        $topic = $app->topicRepository->getTopicById($topicId);

        if($topic === null) {
            $this->flashMessage('Topic #' . $topicId . ' does not exist.', 'error');
            $this->redirect(['page' => 'UserModule:Home', 'action' => 'dashboard']);
        }

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

        $topicMembers = $app->topicMembershipManager->getTopicMemberCount($topicId);
        $postCount = $app->postRepository->getPostCountForTopicId($topicId, !$topic->isDeleted());

        $followLink = '<a class="post-data-link" href="?page=UserModule:Topics&action=follow&topicId=' . $topicId . '">Follow</a>';
        $unFollowLink = '<a class="post-data-link" href="?page=UserModule:Topics&action=unfollow&topicId=' . $topicId . '">Unfollow</a>';
        $isMember = $app->topicMembershipManager->checkFollow($topicId, $app->currentUser->getId());
        $finalFollowLink = '';

        if(!$topic->isDeleted()) {
            $finalFollowLink = ($isMember ? $unFollowLink : $followLink);
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

        $tags = $topic->getTags();

        $tagCode = implode('', $tags);

        $code = '
            <p class="post-data">Followers: ' . $topicMembers . ' ' . $finalFollowLink . '</p>
            <p class="post-data">Topic started on: ' . DateTimeFormatHelper::formatDateToUserFriendly($topic->getDateCreated()) . '</p>
            <p class="post-data">Posts: ' . $postCount . '</p>
            <p class="post-data">Tags: ' . $tagCode . '</p>
            <p class="post-data">' . $reportLink . '</p>
            ' . $deleteLink . '
            ' . $roleManagementLink . '
        ';

        $this->saveToPresenterCache('topicData', $code);

        $postTags = [];
        foreach(PostTags::getAll() as $key => $text) {
            $postTags[] = [
                'value' => $key,
                'text' => $text
            ];
        }

        // new post form
        $fb = new FormBuilder();

        $fb ->setAction(['page' => 'UserModule:Topics', 'action' => 'newPost', 'topicId' => $topicId])
            ->addTextInput('title', 'Title:', null, true)
            ->addTextArea('text', 'Text:', null, true)
            ->addSelect('tag', 'Tag:', $postTags, true)
            ->addSubmit('Post')
        ;

        $this->saveToPresenterCache('newPostForm', $fb);

        if($topic->isDeleted()) {
            $this->addExternalScript('js/Reducer.js');
            $this->addScript('reduceTopicProfile()');
        }

        $links = [];

        if($app->actionAuthorizator->canCreateTopicPoll($app->currentUser->getId(), $topicId)) {
            $links[] = LinkBuilder::createSimpleLink('Create a poll', ['page' => 'UserModule:Topics', 'action' => 'newPollForm', 'topicId' => $topicId], 'post-data-link');
        }

        if($app->actionAuthorizator->canViewTopicPolls($app->currentUser->getId(), $topicId)) {
            $links[] = LinkBuilder::createSimpleLink('Poll list', ['page' => 'UserModule:TopicManagement', 'action' => 'listPolls', 'topicId' => $topicId], 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;', $links));

        if(!empty($links)) {
            $this->saveToPresenterCache('links_hr', '<hr>');
        } else {
            $this->saveToPresenterCache('links_hr', '');
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

        $polls = $app->topicPollRepository->getActivePollBuilderEntitiesForTopic($topicId);

        $pollCode = [];
        $i = 0;
        foreach($polls as $poll) {
            if($i == $limit) {
                break;
            }

            $pollEntity = $app->topicPollRepository->getPollById($poll->getId());
        
            $elapsedTime = new DateTime();
            $elapsedTime->modify($pollEntity->getTimeElapsedForNextVote());
            $elapsedTime = $elapsedTime->getResult();

            $myPollChoice = $app->topicPollRepository->getPollChoice($poll->getId(), $app->currentUser->getId(), $elapsedTime);

            if($myPollChoice !== null) {
                $poll->setUserChoice($myPollChoice);
            }
            
            $poll->setCurrentUserId($app->currentUser->getId());

            $pollCode[] = $poll->render();
            $i++;
        }

        if(empty($posts)) {
            return $this->ajaxSendResponse(['posts' => '<p class="post-text" id="center">No posts found</p>', 'loadMoreLink' => '']);
        }

        $code = [];

        $bwh = new BannedWordsHelper($app->contentRegulationRepository);

        $postIds = [];
        foreach($posts as $post) {
            $postIds[] = $post->getId();
        }

        $likedArray = $app->postRepository->bulkCheckLikes($app->currentUser->getId(), $postIds);

        $postCode = [];
        foreach($posts as $post) {
            $author = $app->userRepository->getUserById($post->getAuthorId());
            $userProfileLink = $app->topicMembershipManager->createUserProfileLinkWithRole($author, $post->getTopicId());
    
            $title = $bwh->checkText($post->getTitle());
    
            $postLink = '<a class="post-title-link" href="?page=UserModule:Posts&action=profile&postId=' . $post->getId() . '">' . $title . '</a>';

            $liked = in_array($post->getId(), $likedArray);
            $likeLink = '<a class="post-like" style="cursor: pointer" href="#post-' . $post->getId() . '-link" onclick="likePost(' . $post->getId() . ', ' . $app->currentUser->getId() . ', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';
    
            $shortenedText = $bwh->checkText($post->getShortenedText(100));
    
            [$tagColor, $tagBgColor] = PostTags::getColorByKey($post->getTag());

            $tmp = '
                <div class="row" id="post-' . $post->getId() . '">
                    <div class="col-md">
                        <div class="row">
                            <div class="col-md-2">
                                <p class="post-data">' . PostTags::createTagText(PostTags::toString($post->getTag()), $tagColor, $tagBgColor) . '</p>
                            </div>

                            <div class="col-md" id="center">
                                <p class="post-title">' . $postLink . '</p>
                            </div>

                            <div class="col-md-2"></div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md">
                                <p class="post-text">' . $shortenedText . '</p>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md">
                                <p class="post-data">Likes: <span id="post-' . $post->getId() . '-likes">' . $post->getLikes() . '</span> <span id="post-' . $post->getId() . '-link">' . $likeLink . '</span>
                                 | Author: ' . $userProfileLink . ' | Date: ' . DateTimeFormatHelper::formatDateToUserFriendly($post->getDateCreated()) . '</p>
                            </div>
                        </div>
                    </div>
                </div>
            ';
    
            $postCode[] = $tmp;
        }

        $pollsFirst = true;

        if($pollsFirst) {
            foreach($pollCode as $pc) {
                $code[] = $pc;
            }
            foreach($postCode as $pc) {
                $code[] = $pc;
            }
        } else {
            foreach($postCode as $pc) {
                $code[] = $pc;
            }
            foreach($pollCode as $pc) {
                $code[] = $pc;
            }
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
        $links = $this->loadFromPresenterCache('links');
        $linksHr = $this->loadFromPresenterCache('links_hr');

        $this->template->topic_title = $topicName;
        $this->template->topic_description = $topicDescription;
        $this->template->latest_posts = $posts;
        $this->template->topic_data = $topicData;
        $this->template->new_post_form = $fb;
        $this->template->links = $links;
        $this->template->links_hr = $linksHr;
    }

    public function handleNewPost(?FormResponse $fr = null) {
        global $app;

        $title = $fr->title;
        $text = $fr->text;
        $tag = $fr->tag;
        $userId = $app->currentUser->getId();
        $topicId = $this->httpGet('topicId');

        try {
            $app->postRepository->createNewPost($topicId, $userId, $title, $text, $tag);
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
            $tags = $fr->tags;

            $topicId = null;

            $tagArray = [];
            foreach(explode(',', $tags) as $tag) {
                $tag = trim($tag);
                $tag = ucfirst($tag);

                [$fg, $bg] = ColorHelper::createColorCombination();
                $tag = '<span style="color: ' . $fg . '; background-color: ' . $bg . '; border: 1px solid ' . $fg . '; border-radius: 10px; padding: 5px; margin-right: 5px">' . $tag . '</span>';

                $tagArray[] = $tag;
            }

            $tags = serialize($tagArray);

            try {
                $app->topicRepository->createNewTopic($title, $description, $tags);
                $topicId = $app->topicRepository->getLastTopicIdForTitle($title);
                $app->topicMembershipManager->followTopic($topicId, $app->currentUser->getId());
                $app->topicMembershipManager->changeRole($topicId, $app->currentUser->getId(), $app->currentUser->getId(), TopicMemberRole::OWNER);

                $cm = new CacheManager($this->logger);
                $cm->invalidateCache('topics');
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
                ->addTextInput('tags', 'Tags:', null, true)
                ->addLabel('Individual tags must be separated by commas - e.g.: technology, art, scifi ...', 'lbl_tags_1')
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

        $topicIdsUserIsMemberOf = $app->topicMembershipManager->getUserMembershipsInTopics($app->currentUser->getId());

        $code = [];

        if(!empty($topicIdsUserIsMemberOf)) {
            foreach($topicIdsUserIsMemberOf as $topicId) {
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
                        <p class="post-text">You are not following any topics.</p>
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

        $notFollowedTopics = $app->topicMembershipManager->getTopicsUserIsNotMemberOf($app->currentUser->getId());

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
                        <p class="post-text">You are following all topics that are available on this platform.</p>
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

    public function handleNewPollForm() {
        global $app;

        $topicId = $this->httpGet('topicId', true);

        if($this->httpGet('isFormSubmit') == 1) {
            $title = $this->httpPost('title');
            $description = $this->httpPost('description');
            $choices = $this->httpPost('choices');
            $dateValid = $this->httpPost('dateValid');
            $timeElapsed = $this->httpPost('timeElapsed');

            $tmp = [];
            foreach(explode(',', $choices) as $choice) {
                $tmp[] = $choice;
            }
            $choices = serialize($tmp);

            if($dateValid == '') {
                $dateValid = null;
            }

            $timeElapsed = '-' . $timeElapsed;

            $app->topicPollRepository->createPoll($title, $description, $app->currentUser->getId(), $topicId, $choices, $dateValid, $timeElapsed);

            $this->flashMessage('Poll created.', 'success');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setMethod()
                ->setAction(['page' => 'UserModule:Topics', 'action' => 'newPollForm', 'topicId' => $topicId])
                ->addTextInput('title', 'Poll title:', null, true)
                ->addTextArea('description', 'Poll description:', null, true)
                ->addTextArea('choices', 'Poll choices code:', null, true)
                ->addLabel('Choice code looks like this: "Pizza,Spaghetti,Pasta" first is the value and the second is the text displayed.', 'clbl1')
                ->addTextInput('timeElapsed', 'Time between votes:', '1d', true)
                ->addLabel('Format must be: count [m - minutes, h - hours, d - days]; e.g.: 1d means 1 day -> 24 hours', 'clbl2')
                ->addDatetime('dateValid', 'Date the poll is available for voting:')
                ->addSubmit('Create')
            ;

            $this->saveToPresenterCache('form', $fb);
        }
    }

    public function renderNewPollForm() {
        $form = $this->loadFromPresenterCache('form');

        $this->template->form = $form;
    }

    public function handlePollSubmit() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $pollId = $this->httpGet('pollId');
        $choice = $this->httpPost('choice');

        $poll = $app->topicPollRepository->getPollById($pollId);
        $elapsedTime = new DateTime();
        $elapsedTime->modify($poll->getTimeElapsedForNextVote());
        $elapsedTime = $elapsedTime->getResult();

        if($app->topicPollRepository->getPollChoice($pollId, $app->currentUser->getId(), $elapsedTime) !== null) {
            $this->flashMessage('You have already voted.', 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        }

        $app->topicPollRepository->submitPoll($pollId, $app->currentUser->getId(), $choice);

        $this->flashMessage('Poll submitted.', 'success');
        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handlePollAnalytics() {
        global $app;

        $pollId = $this->httpGet('pollId', true);

        $poll = $app->topicPollRepository->getPollRowById($pollId);

        $topicId = 0;
        foreach($poll as $row) {
            $topicId = $row['topicId'];
        }

        $userChoices = $app->topicPollRepository->getPollResponses($pollId);

        $cnt = count($userChoices);

        $this->saveToPresenterCache('w1desc', 'Total responses: ' . $cnt);

        $backUrl = ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId];

        if($this->httpGet('backPage') !== null && $this->httpGet('backAction') !== null) {
            $backUrl = ['page' => $this->httpGet('backPage'), 'action' => $this->httpGet('backAction'), 'topicId' => $topicId];
        }

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $backUrl, 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);

        $this->addScript('createWidgets(' . $pollId . ');');
    }

    public function renderPollAnalytics() {
        $w1desc = $this->loadFromPresenterCache('w1desc');
        $links = $this->loadFromPresenterCache('links');

        $this->template->widget1_description = $w1desc;
        $this->template->links = $links;
    }

    public function actionGetPollAnalyticsGraphData() {
        global $app;

        $pollId = $this->httpGet('pollId');
        $poll = $app->topicPollRepository->getPollRowById($pollId);

        $availableChoices = [];
        foreach($poll as $row) {
            $availableChoices = unserialize($row['choices']);
        }

        $userChoices = $app->topicPollRepository->getPollResponsesGrouped($pollId);

        $labels = [];
        $data = [];
        foreach($availableChoices as $k => $text) {
            $labels[] = $text;
            $data[] = $userChoices[$k];
        }

        // generated by chatgpt
        $chartColors = [
            "#1f77b4", "#ff7f0e", "#2ca02c", "#d62728", "#9467bd", "#8c564b", "#e377c2", "#7f7f7f", "#bcbd22", "#17becf", 
            "#aec7e8", "#ffbb78", "#98df8a", "#ff9896", "#c5b0d5", "#c49c94", "#f7b6d2", "#c7c7c7", "#dbdb8d", "#9edae5",
            "#393b79", "#5254a3", "#6b6ecf", "#9c9ede", "#637939", "#8ca252", "#b5cf6b", "#cedb9c", "#8c6d31", "#bd9e39",
            "#e7ba52", "#e7969c", "#d6616b", "#ad494a", "#843c39", "#7b4173", "#a55194", "#ce6dbd", "#de9ed6", "#e7cb94",
            "#e377c2", "#c5b0d5", "#8c564b", "#c49c94", "#f7b6d2", "#c7c7c7", "#bcbd22", "#17becf", "#9edae5", "#dbdb8d"
        ];

        $colors = [];
        for($i = 0; $i < count($availableChoices); $i++) {
            $colors[] = $chartColors[$i];
        }

        $this->ajaxSendResponse(['labels' => $labels, 'data' => $data, 'colors' => $colors]);
    }

    public function handlePollCloseVoting() {
        global $app;

        $pollId = $this->httpGet('pollId', true);
        $topicId = $this->httpGet('topicId', true);

        $app->topicPollRepository->closePoll($pollId);

        $this->flashMessage('Poll closed. You can find it in your profile in the "My polls" section.', 'success');
        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }
}

?>