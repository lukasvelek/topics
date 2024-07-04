<?php

namespace App\Modules\UserModule;

use App\Components\PostLister\PostLister;
use App\Constants\UserProsecutionType;
use App\Core\AjaxRequestBuilder;
use App\Core\CacheManager;
use App\Modules\APresenter;

class HomePresenter extends APresenter {
    public function __construct() {
        parent::__construct('HomePresenter', 'Home');
    }

    public function handleDashboard() {
        global $app;

        /*$followedTopicIds = $app->topicRepository->getFollowedTopicIdsForUser($app->currentUser->getId());
        $followedTopics = $app->topicRepository->bulkGetTopicsByIds($followedTopicIds);*/

        $topicIdsUserIsMemberOf = $app->topicMembershipManager->getUserMembershipsInTopics($app->currentUser->getId());
        $followedTopics = $app->topicRepository->bulkGetTopicsByIds($topicIdsUserIsMemberOf);

        $posts = $app->postRepository->getLatestMostLikedPostsForTopicIds($topicIdsUserIsMemberOf, 10);

        $postLister = new PostLister($app->userRepository, $app->topicRepository, $app->postRepository, $app->contentRegulationRepository);

        $postLister->setPosts($posts);
        $postLister->setTopics($followedTopics);
        $postLister->shufflePosts();
        $postLister->setCurrentUser($app->currentUser);
        
        $this->saveToPresenterCache('postLister', $postLister);
        
        $permaFlashMessages = [];

        $userProsecution = $app->userProsecutionRepository->getLastProsecutionForUserId($app->currentUser->getId());

        if($userProsecution !== null) {
            if($userProsecution->getType() == UserProsecutionType::WARNING) {
                $permaFlashMessages[] = $this->createCustomFlashMessage('warning', 'You have been warned for: ' . $userProsecution->getReason() . '.');
            }
        }

        $this->saveToPresenterCache('permaFlashMessages', $permaFlashMessages);

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
        global $app;

        $postId = $this->httpGet('postId');
        $userId = $app->currentUser->getId();
        $toLike = $this->httpGet('toLike');

        $link = PostLister::createLikeLink($postId, ($toLike == 'true'));
        if($toLike == 'true') {
            $app->postRepository->likePost($userId, $postId);
        } else {
            $app->postRepository->unlikePost($userId, $postId);
        }

        CacheManager::invalidateCache('posts');

        $post = $app->postRepository->getPostById($postId);

        $this->ajaxSendResponse(['likes' => $post->getLikes(), 'link' => $link]);

    }
}

?>