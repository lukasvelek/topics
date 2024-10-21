<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Constants\UserProsecutionType;
use App\Core\AjaxRequestBuilder;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Entities\PostEntity;
use App\Exceptions\AException;
use App\Exceptions\AjaxRequestException;

class HomePresenter extends AUserPresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');

        $this->setDefaultAction('dashboard');
    }

    public function startup() {
        parent::startup();
    }

    public function handleDashboard() {
        $topicIdsUserIsMemberOf = [];
        $followedTopics = $this->app->topicManager->getFollowedTopics($this->getUserId(), $topicIdsUserIsMemberOf);

        $query = $this->app->postRepository->composeQueryForPosts();
        $query->andWhere($query->getColumnInValues('topicId', $topicIdsUserIsMemberOf))
            ->andWhere('isDeleted = 0')
            ->andWhere('dateAvailable <= ?', [DateTime::now()])
            ->andWhere('isSuggestable = 1')
            ->orderBy('likes', 'DESC')
            ->orderBy('dateCreated', 'DESC')
            ->orderBy('postId', 'ASC')
            ->limit(500)
        ;

        $cursor = $query->execute();

        $topicIdCount = [];
        $posts = [];
        while($row = $cursor->fetchAssoc()) {
            $post = PostEntity::createEntityFromDbRow($row);
            if(array_key_exists($post->getTopicId(), $topicIdCount)) {
                if($topicIdCount[$post->getTopicId()] < 2) {
                    $posts[] = $post;
                    $topicIdCount[$post->getTopicId()] = $topicIdCount[$post->getTopicId()] + 1;
                }
            } else {
                $posts[] = $post;
                $topicIdCount[$post->getTopicId()] = 0;
            }
        }

        $postLister = new PostLister($this->app->userRepository, $this->app->topicRepository, $this->app->postRepository, $this->app->contentRegulationRepository, $this->app->fileUploadRepository, $this->app->fileUploadManager, $this->app->reportManager, $this->app->topicManager);

        $postLister->setPosts($posts);
        $postLister->setTopics($followedTopics);
        $postLister->shufflePosts();
        $postLister->setCurrentUser($this->getUser());
        
        $this->saveToPresenterCache('postLister', $postLister);
        
        $permaFlashMessages = [];

        $userProsecution = $this->app->userProsecutionRepository->getLastProsecutionForUserId($this->getUserId());

        if($userProsecution !== null) {
            if($userProsecution->getType() == UserProsecutionType::WARNING) {
                $permaFlashMessages[] = $this->createCustomFlashMessage('warning', 'You have been warned for: ' . $userProsecution->getReason() . '.');
            }
        }

        $permaFlashMessagesCode = '<div class="row">
                <div class="col-md-1 col-lg-1"></div>

                <div class="col-md col-lg">
                    ' . implode('', $permaFlashMessages) . '
                </div>

                <div class="col-md-1 col-lg-1"></div>
            </div>
            <br>';

        if(empty($permaFlashMessages)) {
            $permaFlashMessagesCode = '';
        }

        $this->saveToPresenterCache('permaFlashMessages', $permaFlashMessagesCode);

        $arb = new AjaxRequestBuilder();
        $arb->setURL(['page' => 'UserModule:Home', 'action' => 'likePost'])
            ->setMethod('GET')
            ->setHeader(['postId' => '_postId', 'toLike' => '_like'])
            ->setFunctionName('likePost')
            ->setFunctionArguments(['_postId', '_like'])
            ->updateHTMLElementRaw('"#post-" + _postId + "-likes"', 'likes')
            ->updateHTMLElementRaw('"#post-" + _postId + "-link"', 'link');

        $this->addScript($arb->build());
    }

    public function renderDashboard() {
        $postLister = $this->loadFromPresenterCache('postLister');
        $permaFlashMessages = $this->loadFromPresenterCache('permaFlashMessages');

        $this->template->title = 'Dashboard';
        $this->template->latest_posts = $postLister->render();
        $this->template->permanent_flash_messages = $permaFlashMessages;
    }

    public function actionLikePost() {
        $postId = $this->httpGet('postId');
        $userId = $this->getUserId();
        $toLike = $this->httpGet('toLike');

        $link = PostLister::createLikeLink($postId, ($toLike == 'true'));

        try {
            $this->app->postRepository->beginTransaction();

            if($toLike == 'true') {
                $this->app->postRepository->likePost($userId, $postId);
            } else {
                $this->app->postRepository->unlikePost($userId, $postId);
            }

            $cache = $this->cacheFactory->getCache(CacheNames::POSTS);
            $cache->invalidate();

            $this->app->postRepository->commit($userId, __METHOD__);

            $post = $this->app->postManager->getPostById($this->getUserId(), $postId);
        } catch(AException $e) {
            $this->app->postRepository->rollback();

            throw new AjaxRequestException('Could not like post.', $e);
        }

        return ['likes' => $post->getLikes(), 'link' => $link];
    }
}

?>