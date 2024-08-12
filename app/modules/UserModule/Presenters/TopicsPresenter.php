<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Constants\PostTags;
use App\Constants\ReportCategory;
use App\Constants\TopicMemberRole;
use App\Core\AjaxRequestBuilder;
use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Core\HashManager;
use App\Entities\PostEntity;
use App\Entities\TopicEntity;
use App\Entities\TopicTagEntity;
use App\Entities\UserEntity;
use App\Exceptions\AException;
use App\Helpers\BannedWordsHelper;
use App\Helpers\ColorHelper;
use App\Helpers\DateTimeFormatHelper;
use App\Managers\EntityManager;
use App\UI\FormBuilder\AElement;
use App\UI\FormBuilder\FormBuilder;
use App\UI\FormBuilder\FormResponse;
use App\UI\GridBuilder\Cell;
use App\UI\GridBuilder\GridBuilder;
use App\UI\IRenderable;
use App\UI\LinkBuilder;
use Exception;

class TopicsPresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('TopicsPresenter', 'Topics');
    }

    public function handleProfile() {
        global $app;

        $bwh = new BannedWordsHelper($app->contentRegulationRepository);

        $topicId = $this->httpGet('topicId');

        try {
            $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
        }

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
        $this->addScript('loadPostsForTopic(' . $postLimit . ', 0, \'' . $topicId . '\')');

        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'UserModule:Topics', 'action' => 'likePost'])
            ->setMethod('GET')
            ->setHeader(['postId' => '_postId', 'toLike' => '_toLike'])
            ->setFunctionName('likePost')
            ->setFunctionArguments(['_postId', '_toLike'])
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

        $membership = $app->topicMembershipManager->getFollowRole($topicId, $app->currentUser->getId());
        if($membership != TopicMemberRole::OWNER && ($app->topicMembershipManager->isTopicFollowable($topicId) || $isMember)) {
            $finalFollowLink = ($isMember ? $unFollowLink : $followLink);
        }

        $reportLink = '';

        if(!$topic->isDeleted() && $app->actionAuthorizator->canReportTopic($app->currentUser->getId(), $topicId)) {
            $reportLink = '<div class="col-md col-lg"><a class="post-data-link" href="?page=UserModule:Topics&action=reportForm&topicId=' . $topicId . '">Report topic</a></div>';
        }

        $deleteLink = '';

        if($app->actionAuthorizator->canDeleteTopic($app->currentUser->getId()) && !$topic->isDeleted()) {
            $deleteLink = '<div class="col-md col-lg"><p class="post-data"><a class="post-data-link" href="?page=UserModule:Topics&action=deleteTopic&topicId=' . $topicId . '">Delete topic</a></p></div>';
        } else if($topic->isDeleted()) {
            $deleteLink = '<div class="col-md col-lg"><p class="post-data">Topic deleted</p></div>';
        }

        $roleManagementLink = '';

        if($app->actionAuthorizator->canManageTopicRoles($topicId, $app->currentUser->getId()) && !$topic->isDeleted()) {
            $roleManagementLink = '<div class="col-md col-lg"><p class="post-data"><a class="post-data-link" href="?page=UserModule:TopicManagement&action=manageRoles&topicId=' . $topicId . '">Manage roles</a></div>';
        }

        $tags = $topic->getTags();

        $tagCode = '<div style="line-height: 2.5em">';

        $i = 0;
        foreach($tags as $tag) {
            if($i == 3) {
                $i = 0;

                $tagCode .= '<br>';
            }

            //$tagCode .= $tag;

            if($tag instanceof IRenderable) {
                $tagCode .= $tag->render();
            } else {
                $tagCode .= $tag;
            }

            $i++;
        }

        $tagCode .= '</div>';

        $inviteManagementLink = '';
        if($topic->isPrivate() && $app->actionAuthorizator->canManageTopicInvites($app->currentUser->getId(), $topicId)) {
            $inviteManagementLink = '<div class="col-md col-lg"><p class="post-data">' . LinkBuilder::createSimpleLink('Manage invites', ['page' => 'UserModule:TopicManagement', 'action' => 'listInvites', 'topicId' => $topicId], 'post-data-link') . '</p></div>';
        }

        $privacyManagementLink = '';
        if($app->actionAuthorizator->canManageTopicPrivacy($app->currentUser->getId(), $topicId)) {
            $privacyManagementLink = '<div class="col-md col-lg"><p class="post-data">' . LinkBuilder::createSimpleLink('Manage privacy', ['page' => 'UserModule:TopicManagement', 'action' => 'managePrivacy', 'topicId' => $topicId], 'post-data-link') . '</p></div>';
        }

        $code = '
            <div>
                <div class="row">
                    <div class="col-md col-lg">
                        <p class="post-data">Followers: ' . $topicMembers . ' ' . $finalFollowLink . '</p>
                    </div>

                    <div class="col-md col-lg">
                        <p class="post-data">Posts: ' . $postCount . '</p>                        
                    </div>
                </div>

                <div class="row">
                    <div class="col-md col-lg">
                        <p class="post-data">Tags: ' . $tagCode . '</p>
                    </div>
                </div>

                <div class="row">
                    ' . $reportLink . '
                    ' . $deleteLink . '
                    ' . $roleManagementLink . '
                    ' . $inviteManagementLink . '
                    ' . $privacyManagementLink . '
                </div>
            </div>
        ';

        $this->saveToPresenterCache('topicData', $code);

        $postTags = [];
        foreach(PostTags::getAll() as $key => $text) {
            $postTags[] = [
                'value' => $key,
                'text' => $text
            ];
        }

        if(!$app->topicMembershipManager->checkFollow($topicId, $app->currentUser->getId())) {
            $this->saveToPresenterCache('newPostForm', 'You cannot create posts.');
        }

        $links = [];

        if($app->actionAuthorizator->canCreateTopicPoll($app->currentUser->getId(), $topicId) && !$topic->isDeleted()) {
            $links[] = LinkBuilder::createSimpleLink('Create a poll', ['page' => 'UserModule:Topics', 'action' => 'newPollForm', 'topicId' => $topicId], 'post-data-link');
        }

        if($app->actionAuthorizator->canViewTopicPolls($app->currentUser->getId(), $topicId) && !$topic->isDeleted()) {
            $links[] = LinkBuilder::createSimpleLink('Poll list', ['page' => 'UserModule:TopicManagement', 'action' => 'listPolls', 'topicId' => $topicId], 'post-data-link');
        }

        if($app->actionAuthorizator->canCreatePost($app->currentUser->getId(), $topicId) && !$topic->isDeleted()) {
            array_unshift($links, LinkBuilder::createSimpleLink('Create a post', ['page' => 'UserModule:Topics', 'action' => 'newPostForm', 'topicId' => $topicId], 'post-data-link'));
        }

        if($app->actionAuthorizator->canManageTopicPosts($app->currentUser->getId(), $topic)) {
            $links[] = LinkBuilder::createSimpleLink('Post list', $this->createURL('listPosts', ['topicId' => $topicId]), 'post-data-link');
        }

        $this->saveToPresenterCache('links', implode('&nbsp;&nbsp;', $links));

        if(!empty($links)) {
            $this->saveToPresenterCache('links_br', '<br>');
        } else {
            $this->saveToPresenterCache('links_br', '');
        }
    }

    public function actionLikePost() {
        global $app;

        $userId = $app->currentUser->getId();
        $postId = $this->httpGet('postId');
        $toLike = $this->httpGet('toLike');

        $liked = false;

        try {
            $app->postRepository->beginTransaction();

            if($toLike == 'true') {
                $app->postRepository->likePost($userId, $postId);
                $liked = true;
            } else {
                $app->postRepository->unlikePost($userId, $postId);
            }

            $cm = new CacheManager($app->logger);
            $cm->invalidateCache('posts');

            $app->postRepository->commit($app->currentUser->getId(), __METHOD__);
        } catch(AException $e) {
            $app->postRepository->rollback();

            $this->flashMessage('Post could not be ' . $liked ? 'liked' : 'unliked' . '. Reason: ' . $e->getMessage(), 'error');
        }
 
        $likes = $app->postRepository->getLikes($postId);

        $this->ajaxSendResponse(['postLink' => PostLister::createLikeLink($postId, $liked), 'postLikes' => $likes]);
    }

    public function actionLoadPostsForTopic() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $limit = $this->httpGet('limit');
        $offset = $this->httpGet('offset');

        try {
            $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
        }

        $isMember = $app->topicMembershipManager->checkFollow($topicId, $app->currentUser->getId());

        if(!$isMember && $topic->isPrivate()) {
            return $this->ajaxSendResponse(['posts' => '<p class="post-text" id="center">No posts found</p>', 'loadMoreLink' => '']);
        }

        $posts = $app->postRepository->getLatestPostsForTopicId($topicId, $limit, $offset, !$topic->isDeleted());
        $postCount = $app->postRepository->getPostCountForTopicId($topicId, !$topic->isDeleted());

        $polls = $app->topicPollRepository->getActivePollBuilderEntitiesForTopic($topicId);

        $userRole = $app->topicMembershipManager->getFollowRole($topicId, $app->currentUser->getId());

        $canSeeAnalyticsAllTheTime = false;

        if($userRole >= TopicMemberRole::MANAGER) {
            $canSeeAnalyticsAllTheTime = true;
        }

        $pollCode = [];
        $i = 0;
        foreach($polls as $poll) {
            if($i == $limit) {
                break;
            }

            $pollEntity = $app->topicPollRepository->getPollById($poll->getId());
        
            $elapsedTime = null;
            if($pollEntity->getTimeElapsedForNextVote() != '0') {
                $elapsedTime = new DateTime();
                $elapsedTime->modify($pollEntity->getTimeElapsedForNextVote());
                $elapsedTime = $elapsedTime->getResult();
            }

            $myPollChoice = $app->topicPollRepository->getPollChoice($poll->getId(), $app->currentUser->getId(), $elapsedTime);

            if($myPollChoice !== null) {
                $poll->setUserChoice($myPollChoice->getChoice());
                $poll->setUserChoiceDate($myPollChoice->getDateCreated());
            }
            
            $poll->setCurrentUserId($app->currentUser->getId());
            $poll->setTimeNeededToElapse($pollEntity->getTimeElapsedForNextVote());
            $poll->setUserCanSeeAnalyticsAllTheTime($canSeeAnalyticsAllTheTime);

            $pollCode[] = $poll->render();
            $i++;
        }

        if(empty($posts) && empty($pollCode)) {
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
            $likeLink = '<a class="post-like" style="cursor: pointer" href="#post-' . $post->getId() . '-link" onclick="likePost(\'' . $post->getId() . '\', ' . ($liked ? 'false' : 'true') . ')">' . ($liked ? 'Unlike' : 'Like') . '</a>';
    
            $shortenedText = $bwh->checkText($post->getShortenedText(100));
    
            [$tagColor, $tagBgColor] = PostTags::getColorByKey($post->getTag());

            $imageCode = '';

            $images = $app->fileUploadRepository->getFilesForPost($post->getId());

            if(!empty($images)) {
                $imageJson = [];
                foreach($images as $image) {
                    $imageJson[] = $app->fileUploadManager->createPostImageSourceLink($image);
                }
                $imageJson = json_encode($imageJson);

                $path = $app->fileUploadManager->createPostImageSourceLink($images[0]);

                $imageCode = '<div id="post-' . $post->getId() . '-image-preview-json" style="position: relative; visibility: hidden; width: 0; height: 0">' . $imageJson . '</div><div class="row">';

                // left button
                if(count($images) > 1) {
                    $imageCode .= '<div class="col-md-1"><span id="post-' . $post->getId() . '-image-preview-left-button"></span></div>';
                }

                // image
                $imageCode .= '<div class="col-md"><span id="post-' . $post->getId() . '-image-preview"><a href="#post-' . $post->getId() . '" id="post-' . $post->getId() . '-image-preview-source" onclick="openImagePostLister(\'' . $path . '\', ' . $post->getId() . ')"><img src="' . $path . '" class="limited"></a></span></div>';

                // right button
                if(count($images) > 1) {
                    $imageCode .= '<div class="col-md-1"><span id="post-' . $post->getId() . '-image-preview-right-button"><a href="#post-' . $post->getId() . '" class="post-image-browser-link" onclick="changeImage(' . $post->getId() . ', 1, ' . (count($images) - 1) . ')">&rarr;</a></span></div>';
                }
            }

            $tmp = '
                <div class="row" id="post-id-' . $post->getId() . '">
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

                        <div class="row">' . $imageCode . '</div>

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
            $loadMoreLink = '<a class="post-data-link" onclick="loadPostsForTopic(' . $limit . ',' . ($offset + $limit) . ', \'' . $topicId . '\')" href="#">Load more</a>';
        }

        $this->ajaxSendResponse(['posts' => implode('<br>', $code), 'loadMoreLink' => $loadMoreLink]);
    }

    public function renderProfile() {
        $posts = $this->loadFromPresenterCache('posts');
        $topicData = $this->loadFromPresenterCache('topicData');
        $topicName = $this->loadFromPresenterCache('topicName');
        $topicDescription = $this->loadFromPresenterCache('topicDescription');
        $links = $this->loadFromPresenterCache('links');
        $linksBr = $this->loadFromPresenterCache('links_br');

        $this->template->topic_title = $topicName;
        $this->template->topic_description = $topicDescription;
        $this->template->latest_posts = $posts;
        $this->template->topic_data = $topicData;
        $this->template->links = $links;
        $this->template->links_br = $linksBr;
    }

    public function handleNewPostForm() {
        global $app;

        $topicId = $this->httpGet('topicId', true);

        try {
            $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
        } catch(AException $e) {
            $this->flashMessage('Could not retrieve information about the topic. Reason: ' . $e->getMessage(), 'error');
            $this->redirect(['action' => 'profile', 'topicId' => $topicId]);
        }

        $this->saveToPresenterCache('topicLink', TopicEntity::createTopicProfileLink($topic, false, 'topic-title-link'));

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['topicId' => $topicId]), 'post-data-link')
        ];

        $this->saveToPresenterCache('links', $links);

        // form
        $postTags = [];
        foreach(PostTags::getAll() as $key => $text) {
            $postTags[] = [
                'value' => $key,
                'text' => $text
            ];
        }

        $fb = new FormBuilder();

        $now = new DateTime();
        $now->format('Y-m-d H:i');
        $now = $now->getResult();

        $fb ->setAction(['page' => 'UserModule:Topics', 'action' => 'newPost', 'topicId' => $topicId])
            ->addTextInput('title', 'Title:', null, true)
            ->addTextArea('text', 'Text:', null, true)
            ->addSelect('tag', 'Tag:', $postTags, true)
            ->addFileInput('image', 'Image:')
            ->addDatetime('dateAvailable', 'Available from:', $now, true)
            ->addSubmit('Post')
            ->setCanHaveFiles()
        ;

        $this->saveToPresenterCache('form', $fb);
    }

    public function renderNewPostForm() {
        $topicLink = $this->loadFromPresenterCache('topicLink');
        $links = $this->loadFromPresenterCache('links');
        $form = $this->loadFromPresenterCache('form');

        $this->template->topic_link = $topicLink;
        $this->template->links = $links;
        $this->template->form = $form;
    }

    public function handleNewPost(?FormResponse $fr = null) {
        global $app;

        $title = $fr->title;
        $text = $fr->text;
        $tag = $fr->tag;
        $userId = $app->currentUser->getId();
        $topicId = $this->httpGet('topicId');
        $dateAvailable = $fr->dateAvailable;

        try {
            $app->topicRepository->beginTransaction();

            $postId = $app->entityManager->generateEntityId(EntityManager::POSTS);
            
            $app->postRepository->createNewPost($postId, $topicId, $userId, $title, $text, $tag, $dateAvailable);

            if(isset($_FILES['image']['name']) && $_FILES['image']['name'] != '') {
                $id = $app->postRepository->getLastCreatedPostInTopicByUserId($topicId, $userId)->getId();
            
                $app->fileUploadManager->uploadPostImage($userId, $id, $topicId, $_FILES['image']['name'], $_FILES['image']['tmp_name'], $_FILES['image']);
            }

            $app->topicRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Post created.', 'success');
        } catch(Exception $e) {
            $app->topicRepository->rollback();

            $this->flashMessage('Post could not be created. Error: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handleSearch() {
        global $app;

        $query = $this->httpGet('q');

        $qb = $app->topicRepository->composeQueryForTopicsSearch($query);
        $qb->execute();
        
        $topics = $app->topicRepository->createTopicsArrayFromQb($qb);
        $topics = $app->topicManager->checkTopicsVisibility($topics, $app->currentUser->getId());

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
            $isPrivate = isset($fr->private);

            $topicId = null;

            $tagArray = [];
            $rawTagsArray = [];
            foreach(explode(',', $tags) as $tag) {
                $tag = trim($tag);
                $tag = ucfirst($tag);

                $rawTagsArray[] = $tag;

                [$fg, $bg] = ColorHelper::createColorCombination();
                $tte = new TopicTagEntity($tag, $fg, $bg);

                $tagArray[] = $tte;
            }

            $tags = serialize($tagArray);
            $rawTags = implode(',', $rawTagsArray);

            $tags = str_replace('\\', '\\\\', $tags);

            try {
                $app->topicRepository->beginTransaction();

                $topicId = $app->entityManager->generateEntityId(EntityManager::TOPICS);

                $app->topicRepository->createNewTopic($topicId, $title, $description, $tags, $isPrivate, $rawTags);
                $app->topicMembershipManager->followTopic($topicId, $app->currentUser->getId());
                $app->topicMembershipManager->changeRole($topicId, $app->currentUser->getId(), $app->currentUser->getId(), TopicMemberRole::OWNER);

                $cm = new CacheManager($this->logger);
                $cm->invalidateCache('topics');
                
                $app->topicRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Topic \'' . $title . '\' created.', 'success');
            } catch(AException $e) {
                $app->topicRepository->rollback();

                $this->flashMessage('Could not create a new topic. Reason: ' . $e->getMessage(), 'error');
                $app->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
            }

            $app->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        } else {
            $title = $this->httpGet('title');

            $fb = new FormBuilder();

            $fb ->setAction(['page' => 'UserModule:Topics', 'action' => 'form', 'isSubmit' => '1'])
                ->addTextInput('title', 'Title:', $title, true)
                ->addTextArea('description', 'Description:', null, true)
                ->addTextInput('tags', 'Tags:', null, true)
                ->addLabel('Individual tags must be separated by commas - e.g.: technology, art, scifi ...', 'lbl_tags_1')
                ->addCheckbox('private', 'Is private?')
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
        try {
            $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
        }

        try {
            $app->topicRepository->beginTransaction();

            $app->topicMembershipManager->followTopic($topicId, $app->currentUser->getId());

            $app->topicRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Topic \'' . $topic->getTitle() . '\' followed.', 'success');
        } catch(AException $e) {
            $app->topicRepository->rollback();

            $this->flashMessage('Could not follow topic \'' . $topic->getTitle() . '\'. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handleUnfollow() {
        global $app;

        $topicId = $this->httpGet('topicId');
        try {
            $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
        } catch(AException $e) {
            $this->flashMessage($e->getMessage(), 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
        }

        try {
            $app->topicRepository->beginTransaction();
            
            $app->topicMembershipManager->unfollowTopic($topicId, $app->currentUser->getId());

            $app->topicRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Topic \'' . $topic->getTitle() . '\' unfollowed.', 'success');
        } catch(AException $e) {
            $app->topicRepository->rollback();

            $this->flashMessage('Could not unfollow topic \'' . $topic->getTitle() . '\'. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handleFollowed() {
        global $app;

        $topicIdsUserIsMemberOf = $app->topicMembershipManager->getUserMembershipsInTopics($app->currentUser->getId());

        $code = [];

        if(!empty($topicIdsUserIsMemberOf)) {
            $first = true;
            $tmpCode = '';
            
            foreach($topicIdsUserIsMemberOf as $topicId) {
                try {
                    $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
                } catch(AException $e) {
                    continue;
                }

                if(count($topicIdsUserIsMemberOf) > 1) {
                    if($first) {
                        $tmpCode = '<div class="row"><div class="col-md col-lg" id="topic-followed-section">';
                        $tmpCode .= '<a class="post-title-link" href="?page=UserModule:Topics&action=profile&topicId=' . $topicId . '">' . $topic->getTitle() . '</a>';
                        $tmpCode .= '</div><div class="col-md-1 col-lg-1"></div>';
    
                        $first = false;
                    } else {
                        $tmpCode .= '<div class="col-md col-lg" id="topic-followed-section">';
                        $tmpCode .= '<a class="post-title-link" href="?page=UserModule:Topics&action=profile&topicId=' . $topicId . '">' . $topic->getTitle() . '</a>';
                        $tmpCode .= '</div></div>';
    
                        $first = true;
                    }
                } else {
                    $tmpCode = '<div class="row"><div class="col-md col-lg" id="topic-followed-section">';
                    $tmpCode .= '<a class="post-title-link" href="?page=UserModule:Topics&action=profile&topicId=' . $topicId . '">' . $topic->getTitle() . '</a>';
                    $tmpCode .= '</div></div>';
                }

                $code[] = $tmpCode;
            }
        } else {
            $code[] = '
                <div class="row">
                    <div class="col-md col-lg" id="topic-followed-section">
                        <p class="post-text" id="center">You are not following any topics.</p>
                    </div>
                </div>
            ';
        }

        $this->saveToPresenterCache('topics', implode('<br>', $code));
    }

    public function renderFollowed() {
        $topics = $this->loadFromPresenterCache('topics');
        $this->template->topics = $topics;
    }

    public function handleDiscover() {
        global $app;

        $notFollowedTopicIds = $app->topicMembershipManager->getTopicIdsUserIsNotMemberOf($app->currentUser->getId());
        $notFollowedTopics = $app->topicManager->getTopicsNotInIdArray($notFollowedTopicIds, $app->currentUser->getId());

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
                        <p class="post-text" id="center">You are following all topics that are available on this platform.</p>
                    </div>
                </div>
            ';
        }

        $this->saveToPresenterCache('topics', implode('', $code));

        $trendingLinks = [
            LinkBuilder::createSimpleLink('Discover', $this->createURL('discover'), 'post-data-link'),
            LinkBuilder::createSimpleLink('Trending', $this->createURL('trending'), 'post-data-link')
        ];

        $this->saveToPresenterCache('trendingLinks', implode('&nbsp;', $trendingLinks));
    }

    public function renderDiscover() {
        $topics = $this->loadFromPresenterCache('topics');
        $trendingLinks = $this->loadFromPresenterCache('trendingLinks');

        $this->template->topics = $topics;
        $this->template->trending_links = $trendingLinks;
    }

    public function handleTrending() {
        $trendingLinks = [
            LinkBuilder::createSimpleLink('Discover', $this->createURL('discover'), 'post-data-link'),
            LinkBuilder::createSimpleLink('Trending', $this->createURL('trending'), 'post-data-link')
        ];

        $this->saveToPresenterCache('trendingLinks', implode('&nbsp;', $trendingLinks));

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setAction($this, 'getTrendingTopicsList')
            ->setFunctionName('getTrendingTopicsList')
            ->updateHTMLElement('trending-topics-list', 'list')
        ;

        $this->addScript($arb);
        $this->addScript('getTrendingTopicsList()');

        $arb = new AjaxRequestBuilder();

        $arb->setMethod()
            ->setAction($this, 'getTrendingPostsList')
            ->setFunctionName('getTrendingPostsList')
            ->updateHTMLElement('trending-posts-list', 'list')
        ;

        $this->addScript($arb);
        $this->addScript('getTrendingPostsList()');
    }

    public function renderTrending() {
        $trendingLinks = $this->loadFromPresenterCache('trendingLinks');

        $this->template->trending_links = $trendingLinks;
    }

    public function actionGetTrendingTopicsList() {
        global $app;

        $limit = 5;

        // topics with most new posts in 24 hrs
        $data = $app->postRepository->getTopicIdsWithMostPostsInLast24Hrs($limit); // topicId => cnt

        $codeArray = [];
        foreach($data as $topicId => $cnt) {
            $topic = $app->topicRepository->getTopicById($topicId);
            $link = TopicEntity::createTopicProfileLink($topic);
            $codeArray[] = '
                <div class="row">
                    <div class="col-md">
                        ' . $link . '
                    </div>

                    <div class="col-md">
                        ' . $cnt . ' posts
                    </div>
                </div>';
        }

        $this->ajaxSendResponse(['list' => implode('<br>', $codeArray)]);
    }

    public function actionGetTrendingPostsList() {
        global $app;

        $limit = 5;

        $data = $app->postCommentRepository->getPostIdsWithMostCommentsInLast24Hrs($limit);

        $codeArray = [];
        foreach($data as $postId => $cnt) {
            $post = $app->postRepository->getPostById($postId);

            $link = LinkBuilder::createSimpleLink($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $postId], 'post-data-link');
            
            $codeArray[] = '
                <div class="row">
                    <div class="col-md">
                        ' . $link . '
                    </div>

                    <div class="col-md">
                        ' . $cnt . ' comments
                    </div>
                </div>';
        }

        $this->ajaxSendResponse(['list' => implode('<br>', $codeArray)]);
    }

    public function handleReportForm(?FormResponse $fr = null) {
        global $app;

        $topicId = $this->httpGet('topicId');
        
        if($this->httpGet('isSubmit') !== null && $this->httpGet('isSubmit') == '1') {
            $category = $fr->category;
            $description = $fr->description;
            $userId = $app->currentUser->getId();

            try {
                $app->reportRepository->beginTransaction();

                $app->reportRepository->createTopicReport($userId, $topicId, $category, $description);

                $app->reportRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Topic reported.', 'success');
            } catch(AException $e) {
                $app->reportRepository->rollback();

                $this->flashMessage('Could not report topic. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        } else {
            try {
                $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
            } catch(AException $e) {
                $this->flashMessage($e->getMessage(), 'error');
                $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
            }
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

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['topicId' => $topicId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderReportForm() {
        $topic = $this->loadFromPresenterCache('topic');
        $form = $this->loadFromPresenterCache('form');
        $links = $this->loadFromPresenterCache('links');

        $this->template->topic_title = $topic->getTitle();
        $this->template->form = $form;
        $this->template->links = $links;
    }

    public function handleDeleteTopic(?FormResponse $fr = null) {
        global $app;

        $topicId = $this->httpGet('topicId');

        if($this->httpGet('isSubmit') == '1') {
            try {
                $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
            } catch(AException $e) {
                $this->flashMessage('Could not delete this topic. Reason: ' . $e->getMessage(), 'error');
                $this->redirect(['action' => 'profile', 'topicId' => $topicId]);
            }

            $topicLink = TopicEntity::createTopicProfileLink($topic, true);
            $userLink = UserEntity::createUserProfileLink($app->currentUser, true);

            $topicOwnerId = $app->topicMembershipManager->getTopicOwnerId($topicId);
            
            try {
                $app->topicRepository->beginTransaction();

                $app->topicManager->deleteTopic($topicId, $app->currentUser->getId());

                $app->notificationManager->createNewTopicDeletedNotification($topicOwnerId, $topicLink, $userLink);

                $app->topicRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Topic #' . $topicId . ' has been deleted.', 'success');
            } catch(Exception $e) {
                $app->topicRepository->rollback();

                $this->flashMessage('Topic #' . $topicId . ' could not be deleted. Reason: ' . $e->getMessage(), 'error');
            }

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
            $pollId = $app->entityManager->generateEntityId(EntityManager::TOPIC_POLLS);

            $tmp = [];
            foreach(explode(',', $choices) as $choice) {
                $tmp[] = $choice;
            }
            $choices = serialize($tmp);

            if($dateValid == '') {
                $dateValid = null;
            }

            if($timeElapsed != '0') {
                $timeElapsed = '-' . $timeElapsed;
            }

            try {
                $app->topicPollRepository->beginTransaction();

                $app->topicPollRepository->createPoll($pollId, $title, $description, $app->currentUser->getId(), $topicId, $choices, $dateValid, $timeElapsed);

                $app->topicPollRepository->commit($app->currentUser->getId(), __METHOD__);

                $this->flashMessage('Poll created.', 'success');
            } catch(AException $e) {
                $app->topicPollRepository->rollback();

                $this->flashMessage('Could not create poll. Reason: ' . $e->getMessage(), 'error');
            }

            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        } else {
            $fb = new FormBuilder();

            $fb ->setMethod()
                ->setAction(['page' => 'UserModule:Topics', 'action' => 'newPollForm', 'topicId' => $topicId])
                ->addTextInput('title', 'Poll title:', null, true)
                ->addTextArea('description', 'Poll description:', null, true)
                ->addTextArea('choices', 'Poll choices:', null, true)
                ->addLabel('Choices should be formatted this way: <i>Pizza, Spaghetti, Pasta</i>.', 'clbl1')
                ->addTextInput('timeElapsed', 'Time between votes:', '1d', true)
                ->addLabel('Format must be: count [m - minutes, h - hours, d - days]; e.g.: 1d means 1 day -> 24 hours, 0 means single-vote-only', 'clbl2')
                ->addDatetime('dateValid', 'Date the poll is available for voting:')
                ->addSubmit('Create')
            ;

            $fb->updateElement('choices', function(AElement $element) {
                $element->maxlength = '32768';
                return $element;
            });

            $this->saveToPresenterCache('form', $fb);

            try {
                $topic = $app->topicManager->getTopicById($topicId, $app->currentUser->getId());
            } catch(AException $e) {
                $this->flashMessage($e->getMessage(), 'error');
                $this->redirect(['page' => 'UserModule:Topics', 'action' => 'discover']);
            }

            $topicLink = LinkBuilder::createSimpleLink($topic->getTitle(), ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topic->getId()], 'topic-title-link');

            $this->saveToPresenterCache('topicLink', $topicLink);

            $links = [
                LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['topicId' => $topicId]), 'post-data-link')
            ];

            $this->saveToPresenterCache('links', $links);
        }
    }

    public function renderNewPollForm() {
        $form = $this->loadFromPresenterCache('form');
        $topicLink = $this->loadFromPresenterCache('topicLink');
        $links = $this->loadFromPresenterCache('links');

        $this->template->form = $form;
        $this->template->topic_link = $topicLink;
        $this->template->links = $links;
    }

    public function handlePollSubmit() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $pollId = $this->httpGet('pollId');
        $choice = $this->httpPost('choice');

        $poll = $app->topicPollRepository->getPollById($pollId);
        
        $elapsedTime = null;
        if($poll->getTimeElapsedForNextVote() != '0') {
            $elapsedTime = new DateTime();
            $elapsedTime->modify($poll->getTimeElapsedForNextVote());
            $elapsedTime = $elapsedTime->getResult();
        }

        if($app->topicPollRepository->getPollChoice($pollId, $app->currentUser->getId(), $elapsedTime) !== null) {
            $this->flashMessage('You have already voted.', 'error');
            $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
        }

        try {
            $app->topicPollRepository->beginTransaction();
            
            $responseId = $app->entityManager->generateEntityId(EntityManager::TOPIC_POLL_RESPONSES);

            $app->topicPollRepository->submitPoll($responseId, $pollId, $app->currentUser->getId(), $choice);

            $app->topicPollRepository->commit($app->currentUser->getId(), __METHOD__);

            $this->flashMessage('Poll submitted.', 'success');
        } catch(AException $e) {
            $app->topicPollRepository->rollback();

            $this->flashMessage('Could not submit poll vote. Reason: ' . $e->getMessage(), 'error');
        }

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

        $this->addScript('createWidgets(\'' . $pollId . '\');');
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

            if(isset($userChoices[$k])) {
                $data[] = $userChoices[$k];
            } else {
                $data[] = 0;
            }
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

        try {
            $app->topicPollRepository->beginTransaction();

            $app->topicPollRepository->closePoll($pollId);

            $app->topicPollRepository->commit($app->currentUser->getId(), __METHOD__);
            
            $this->flashMessage('Poll closed. You can find it in your profile in the "My polls" section.', 'success');
        } catch(AException $e) {
            $app->topicPollRepository->rollback();

            $this->flashMessage('Could not close poll. Reason: ' . $e->getMessage(), 'error');
        }

        $this->redirect(['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topicId]);
    }

    public function handleListPosts() {
        $topicId = $this->httpGet('topicId', true);
        $filter = $this->httpGet('filter') ?? 'null';

        $arb = new AjaxRequestBuilder();
        $arb->setMethod()
            ->setHeader(['gridPage' => '_page', 'topicId' => '_topicId', 'filter' => '_filter'])
            ->setAction($this, 'getPostGrid')
            ->setFunctionName('getPostGrid')
            ->setFunctionArguments(['_page', '_topicId', '_filter'])
            ->updateHTMLElement('grid-content', 'grid')
        ;

        $this->addScript($arb);
        $this->addScript('getPostGrid(0, \'' . $topicId .'\', \'' . $filter . '\')');

        $links = [
            LinkBuilder::createSimpleLink('&larr; Back', $this->createURL('profile', ['topicId' => $topicId]), 'post-data-link'),
            LinkBuilder::createSimpleLink('All', $this->createURL('listPosts', ['topicId' => $topicId]), 'post-data-link'),
            LinkBuilder::createSimpleLink('Scheduled', $this->createURL('listPosts', ['topicId' => $topicId, 'filter' => 'scheduled']), 'post-data-link')
        ];

        $links = implode('&nbsp;&nbsp;', $links);

        $this->saveToPresenterCache('links', $links);
    }

    public function renderListPosts() {
        $links = $this->loadFromPresenterCache('links');

        $this->template->links = $links;
    }

    public function actionGetPostGrid() {
        global $app;

        $topicId = $this->httpGet('topicId');
        $filter = $this->httpGet('filter');

        $page = $this->httpGet('gridPage');
        $gridSize = $app->getGridSize();
        $offset = $page * $gridSize;

        if($filter == 'scheduled') {
            $posts = $app->postRepository->getScheduledPostsForTopicForGrid($topicId, $gridSize, $offset);
            $totalCount = count($app->postRepository->getScheduledPostsForTopicForGrid($topicId, 0, 0));
        } else {
            $posts = $app->postRepository->getPostsForTopicForGrid($topicId, $gridSize, $offset);
            $totalCount = count($app->postRepository->getPostsForTopicForGrid($topicId, 0, 0));
        }

        $lastPage = ceil($totalCount / $gridSize);

        $gb = new GridBuilder();
        $gb->addColumns(['title' => 'Title', 'author' => 'Author', 'dateAvailable' => 'Available from', 'dateCreated' => 'Date created']);
        $gb->addDataSource($posts);
        $gb->addOnColumnRender('title', function(Cell $cell, PostEntity $post) {
            return LinkBuilder::createSimpleLink($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'grid-link');
        });
        $gb->addOnColumnRender('author', function(Cell $cell, PostEntity $post) use ($app) {
            $user = $app->userRepository->getUserById($post->getAuthorId());

            $link = UserEntity::createUserProfileLink($user, true);
            $link->setClass('grid-link');

            return $link->render();
        });
        $gb->addOnColumnRender('dateAvailable', function(Cell $cell, PostEntity $post) use ($filter) {
            $date = DateTimeFormatHelper::formatDateToUserFriendly($post->getDateAvailable());

            $cell->setValue($date);

            if(($post->getDateCreated() != $post->getDateAvailable()) && strtotime($post->getDateAvailable()) > time() && $filter != 'scheduled') { // if the post is scheduled and current filter does not limit posts to scheduled only
                $cell->setTextColor('orange');
            }
            
            return $cell;
        });
        $gb->addOnColumnRender('dateCreated', function(Cell $cell, PostEntity $post) {
            return DateTimeFormatHelper::formatDateToUserFriendly($post->getDateCreated());
        });
        $gb->addGridPaging($page, $lastPage, $gridSize, $totalCount, 'getPostGrid', [$topicId]);

        $this->ajaxSendResponse(['grid' => $gb->build()]);
    }
}

?>