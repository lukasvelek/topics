<?php

namespace App\Managers;

use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicEntity;
use App\Entities\UserActionEntity;
use App\Exceptions\GeneralException;
use App\Helpers\DateTimeFormatHelper;
use App\Logger\Logger;
use App\Repositories\PostCommentRepository;
use App\Repositories\PostRepository;
use App\Repositories\TopicPollRepository;
use App\Repositories\TopicRepository;
use App\UI\LinkBuilder;

class ContentManager extends AManager {
    private const T_TOPIC = 1;
    private const T_POST = 2;
    private const T_COMMENT = 3;

    private TopicRepository $topicRepository;
    private PostRepository $postRepository;
    private PostCommentRepository $postCommentRepository;
    private TopicMembershipManager $topicMembershipManager;
    private TopicPollRepository $topicPollRepository;

    private bool $fullDelete;

    public function __construct(TopicRepository $topicRepository, PostRepository $postRepository, PostCommentRepository $postCommentRepository, bool $full_delete, Logger $logger, TopicMembershipManager $topicMembershipManager, TopicPollRepository $topicPollRepository, EntityManager $entityManager) {
        parent::__construct($logger, $entityManager);
        
        $this->topicRepository = $topicRepository;
        $this->postCommentRepository = $postCommentRepository;
        $this->postRepository = $postRepository;
        $this->topicMembershipManager = $topicMembershipManager;
        $this->topicPollRepository = $topicPollRepository;

        $this->fullDelete = $full_delete;
    }

    public function deleteTopic(string $topicId, bool $deleteCache = true) {
        $posts = $this->postRepository->getLatestPostsForTopicId($topicId, 0);

        foreach($posts as $post) {
            $this->deletePost($post->getId(), false);
        }

        $this->topicRepository->deleteTopic($topicId, $this->isHide());

        $this->afterDelete(self::T_TOPIC, $deleteCache);
        $this->afterDelete(self::T_POST, $deleteCache);
    }

    public function deletePost(string $postId, bool $deleteCache = true) {
        $comments = $this->postCommentRepository->getCommentsForPostId($postId);

        foreach($comments as $comment) {
            $this->deleteComment($comment->getId(), $deleteCache);
        }

        $this->postRepository->deletePost($postId, $this->isHide());

        $this->afterDelete(self::T_POST, $deleteCache);
    }

    public function deleteComment(string $commentId, bool $deleteCache = true) {
        $this->postCommentRepository->deleteComment($commentId, $this->isHide());

        $this->afterDelete(self::T_COMMENT, $deleteCache);
    }

    private function isHide() {
        return !$this->fullDelete;
    }

    private function afterDelete(int $type, bool $deleteCache) {
        $cm = new CacheManager($this->postRepository->getLogger());

        if($deleteCache) {
            switch($type) {
                case self::T_POST:
                    $cm->invalidateCache('posts');
                    break;
                
                case self::T_TOPIC:
                    $cm->invalidateCache('topics');
                    $cm->invalidateCache('topicMemberships');
                    break;
            }
        }
    }

    public function updateTopic(string $topicId, array $data) {
        return $this->topicRepository->updateTopic($topicId, $data);
    }

    public function getUserActionHistory(string $userId, int $limit = 10) {
        $maxDate = new DateTime();
        $maxDate->modify('-7d');
        $maxDate = $maxDate->getResult();
        
        $actions = [];
        
        $posts = $this->postRepository->getPostsCreatedByUser($userId, $maxDate);
        if(!empty($posts)) {
            foreach($posts as $post) {
                $actions[] = new UserActionEntity($post->getId(), UserActionEntity::TYPE_POST, $post->getDateCreated());
            }
        }

        $postComments = $this->postCommentRepository->getCommentsForUser($userId, $maxDate);
        if(!empty($postComments)) {
            foreach($postComments as $pc) {
                $actions[] = new UserActionEntity($pc->getId(), UserActionEntity::TYPE_POST_COMMENT, $pc->getDateCreated());
            }
        }

        $topics = $this->topicMembershipManager->getTopicsWhereUserIsOwnerOrderByTopicDateCreated($userId);
        if(!empty($topics)) {
            foreach($topics as $t) {
                $actions[] = new UserActionEntity($t->getId(), UserActionEntity::TYPE_TOPIC, $t->getDateCreated());
            }
        }

        $polls = $this->topicPollRepository->getPollCreatedByUserOrderedByDateDesc($userId);
        if(!empty($polls)) {
            foreach($polls as $p) {
                $actions[] = new UserActionEntity($p->getId(), UserActionEntity::TYPE_POLL, $p->getDateCreated());
            }
        }

        $pollVotes = $this->topicPollRepository->getPollResponsesForUserOrderedByDateDesc($userId);
        if(!empty($pollVotes)) {
            foreach($pollVotes as $pv) {
                $actions[] = new UserActionEntity($pv->getId(), UserActionEntity::TYPE_POLL_VOTE, $pv->getDateCreated());
            }
        }

        $orderedActions = [];
        foreach($actions as $action) {
            $orderedActions[] = strtotime($action->getDateCreated());
        }

        rsort($orderedActions, SORT_NATURAL);

        $orderedActionsComplete = [];
        foreach($orderedActions as $ts) {
            foreach($actions as $action) {
                if(strtotime($action->getDateCreated()) == $ts) {
                    $orderedActionsComplete[] = $action;
                }
            }
        }

        $code = '<div>';
        $i = 0;
        $codeArray = [];
        foreach($orderedActionsComplete as $oal) {
            if($i == $limit) {
                break;
            }
            $date = '[' . DateTimeFormatHelper::formatDateToUserFriendly($oal->getDateCreated()) . ']';
            $text = '';
            switch($oal->getType()) {
                case UserActionEntity::TYPE_TOPIC:
                    $topic = $this->topicRepository->getTopicById($oal->getId());
                    $topicLink = TopicEntity::createTopicProfileLink($topic);
                    $text = 'Created topic <u>' . $topicLink . '</u>.';
                    break;

                case UserActionEntity::TYPE_POST:
                    $post = $this->postRepository->getPostById($oal->getId());
                    $postLink = LinkBuilder::createSimpleLink($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'post-data-link');
                    $text = 'Created post <u>' . $postLink . '</u>.';
                    break;

                case UserActionEntity::TYPE_POST_COMMENT:
                    $comment = $this->postCommentRepository->getCommentById($oal->getId());
                    $post = $this->postRepository->getPostById($comment->getPostId());
                    $postLink = LinkBuilder::createSimpleLink($post->getTitle(), ['page' => 'UserModule:Posts', 'action' => 'profile', 'postId' => $post->getId()], 'post-data-link');
                    $text = 'Posted comment on post <u>' . $postLink . '</u>.';
                    break;

                case UserActionEntity::TYPE_POLL:
                    $poll = $this->topicPollRepository->getPollById($oal->getId());
                    $topic = $this->topicRepository->getTopicById($poll->getTopicId());
                    $topicLink = TopicEntity::createTopicProfileLink($topic);
                    $text = 'Created poll in topic <u>' . $topicLink . '</u>.';
                    break;

                case UserActionEntity::TYPE_POLL_VOTE:
                    $pollVote = $this->topicPollRepository->getPollResponseById($oal->getId());
                    $poll = $this->topicPollRepository->getPollById($pollVote->getPollId());
                    $topic = $this->topicRepository->getTopicById($poll->getTopicId());
                    $topicLink = TopicEntity::createTopicProfileLink($topic);
                    $text = 'Voted in poll in topic <u>' . $topicLink . '</u>.';
                    break;
            }

            $codeArray[] = '<div id="user-action-history-' . $i . '"><p><span style="color: rgb(100, 100, 100)">' . $date . '</span> ' . $text . '</p></div>';
            $i++;
        }
        $code .= implode('<br>', $codeArray) . '</div>';

        return $code;
    }

    public function updatePost(string $postId, array $data) {
        return $this->postRepository->updatePost($postId, $data);
    }

    public function pinPost(string $topicId, string $postId, bool $pin = true) {
        $result = true;
        $data = [];
        if($pin) {
            $pinId = $this->postRepository->createEntityId(EntityManager::TOPIC_POST_PINS);
            $result = $this->postRepository->createNewPostPin($pinId, $topicId, $postId);
            $data = ['isSuggestable' => '0'];
        } else {
            $result = $this->postRepository->removePostPin($topicId, $postId);
            $data = ['isSuggestable' => '1'];
        }

        if(!$result) {
            throw new GeneralException('Could not ' . ($pin ? 'pin' : 'unpin') . ' post.');
        }

        if(!$this->updatePost($postId, $data)) {
            throw new GeneralException('Could not change suggestion of post.');
        }
    }
}

?>