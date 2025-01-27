<?php

namespace App\Managers;

use App\Core\Caching\CacheNames;
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

/**
 * ContentManager is responsible for managing content
 * 
 * @author Lukas Velek
 */
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

    /**
     * Class constructor
     * 
     * @param TopicRepository $topicRepository TopicRepository instance
     * @param PostRepository $postRepository PostRepository instance
     * @param PostCommentRepository $postCommentRepository PostCommentRepository instance
     * @param bool $full_delete True if the content should be deleted fully or false if it should just have isDeleted attribute set to 1
     * @param Logger $logger Logger instance
     * @param TopicMembershipManager $topicMembershipManager TopicMembershipManager instance
     * @param TopicPollRepository $topicPollRepository TopicPollRepository instance
     * @param EntityManager $entityManager EntityManager instance
     */
    public function __construct(TopicRepository $topicRepository, PostRepository $postRepository, PostCommentRepository $postCommentRepository, bool $full_delete, Logger $logger, TopicMembershipManager $topicMembershipManager, TopicPollRepository $topicPollRepository, EntityManager $entityManager) {
        parent::__construct($logger, $entityManager);
        
        $this->topicRepository = $topicRepository;
        $this->postCommentRepository = $postCommentRepository;
        $this->postRepository = $postRepository;
        $this->topicMembershipManager = $topicMembershipManager;
        $this->topicPollRepository = $topicPollRepository;

        $this->fullDelete = $full_delete;
    }

    /**
     * Deletes a topic
     * 
     * @param string $topicId Topic ID
     * @param bool $deleteCache Delete cache?
     */
    public function deleteTopic(string $topicId, bool $deleteCache = true) {
        // posts
        $posts = $this->postRepository->getLatestPostsForTopicId($topicId, 0);

        foreach($posts as $post) {
            $this->deletePost($post->getId(), false);
        }

        // polls
        $polls = $this->topicPollRepository->getPollsForTopicForGrid($topicId, 0, 0);

        foreach($polls as $poll) {
            $this->deletePoll($poll->getId());
        }

        $this->topicRepository->deleteTopic($topicId, $this->isHide());

        $this->afterDelete(self::T_TOPIC, $deleteCache);
        $this->afterDelete(self::T_POST, $deleteCache);
    }

    /**
     * Deletes a poll
     * 
     * @param string $pollId Poll ID
     */
    public function deletePoll(string $pollId) {
        $this->topicPollRepository->deletePollResponsesForPollId($pollId);
        $this->topicPollRepository->deletePoll($pollId);
    }

    /**
     * Deletes a post
     * 
     * @param string $postId Post ID
     * @param bool $deleteCache Delete cache?
     */
    public function deletePost(string $postId, bool $deleteCache = true) {
        $comments = $this->postCommentRepository->getCommentsForPostId($postId);

        foreach($comments as $comment) {
            $this->deleteComment($comment->getId(), $deleteCache);
        }

        $this->postRepository->deletePost($postId, $this->isHide());

        $this->afterDelete(self::T_POST, $deleteCache);
    }

    /**
     * Deletes a comment
     * 
     * @param string $commentId
     * @param bool $deleteCache Delete cache?
     */
    public function deleteComment(string $commentId, bool $deleteCache = true) {
        $this->postCommentRepository->deleteComment($commentId, $this->isHide());

        $this->afterDelete(self::T_COMMENT, $deleteCache);
    }

    /**
     * Returns if the content should be fully deleted or just hidden
     * 
     * @return bool True if the content should be just hidden or false if it should be fully deleted
     */
    private function isHide() {
        return !$this->fullDelete;
    }

    /**
     * Processes operations after the entity has been deleted
     * 
     * @param int $type Entity type
     * @param bool $deleteCache Delete cache?
     */
    private function afterDelete(int $type, bool $deleteCache) {
        if($deleteCache) {
            switch($type) {
                case self::T_POST:
                    $postsCache = $this->cacheFactory->getCache(CacheNames::POSTS);
                    $postsCache->invalidate();

                    $pinnedPostsCache = $this->cacheFactory->getCache(CacheNames::PINNED_POSTS);
                    $pinnedPostsCache->invalidate();
                    break;
                
                case self::T_TOPIC:
                    $topicsCache = $this->cacheFactory->getCache(CacheNames::TOPICS);
                    $topicsCache->invalidate();

                    $topicMembershipsCache = $this->cacheFactory->getCache(CacheNames::TOPIC_MEMBERSHIPS);
                    $topicMembershipsCache->invalidate();

                    $topicRulesCache = $this->cacheFactory->getCache(CacheNames::TOPIC_RULES);
                    $topicRulesCache->invalidate();
                    break;
            }
        }
    }

    /**
     * Updates a topic
     * 
     * @param string $topicId Topic ID
     * @param array $data Data
     * @return bool True if the operation was successful or false if not
     */
    public function updateTopic(string $topicId, array $data) {
        return $this->topicRepository->updateTopic($topicId, $data);
    }

    /**
     * Returns user action history list
     * 
     * @param string $userId User ID
     * @param int $limit Limit of list entries
     * @return string HTML code
     */
    public function getUserActionHistory(string $userId, int $limit = 10) {
        $maxDate = new DateTime();
        $maxDate->modify('-7d');
        $maxDate = $maxDate->getResult();

        $dbLimit = 10;
        
        $actions = [];
        
        $posts = $this->postRepository->getPostsCreatedByUser($userId, $maxDate, $dbLimit);
        if(!empty($posts)) {
            foreach($posts as $post) {
                $actions[] = new UserActionEntity($post->getId(), UserActionEntity::TYPE_POST, $post->getDateCreated());
            }
        }

        $postComments = $this->postCommentRepository->getCommentsForUser($userId, $maxDate, $dbLimit);
        if(!empty($postComments)) {
            foreach($postComments as $pc) {
                $actions[] = new UserActionEntity($pc->getId(), UserActionEntity::TYPE_POST_COMMENT, $pc->getDateCreated());
            }
        }

        $topics = $this->topicMembershipManager->getTopicsWhereUserIsOwnerOrderByTopicDateCreated($userId, $dbLimit);
        if(!empty($topics)) {
            foreach($topics as $t) {
                $actions[] = new UserActionEntity($t->getId(), UserActionEntity::TYPE_TOPIC, $t->getDateCreated());
            }
        }

        $polls = $this->topicPollRepository->getPollCreatedByUserOrderedByDateDesc($userId, $dbLimit);
        if(!empty($polls)) {
            foreach($polls as $p) {
                $actions[] = new UserActionEntity($p->getId(), UserActionEntity::TYPE_POLL, $p->getDateCreated());
            }
        }

        $pollVotes = $this->topicPollRepository->getPollResponsesForUserOrderedByDateDesc($userId, $dbLimit);
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

            $dateAtomic = DateTimeFormatHelper::formatDateToUserFriendly($oal->getDateCreated(), DateTimeFormatHelper::ATOM_FORMAT);

            $codeArray[] = '<div id="user-action-history-' . $i . '"><p><span style="color: rgb(100, 100, 100)" title="' . $dateAtomic . '">' . $date . '</span> ' . $text . '</p></div>';
            $i++;
        }
        $code .= implode('<br>', $codeArray) . '</div>';

        return $code;
    }

    /**
     * Updates a post
     * 
     * @param string $postId Post ID
     * @param array $data Data
     * @return bool True if the operation was successful or false if not
     */
    public function updatePost(string $postId, array $data) {
        return $this->postRepository->updatePost($postId, $data);
    }

    /**
     * Pins a post
     * 
     * @param string $topicId Topic ID
     * @param string $postId Post ID
     * @param bool $pin True if the post should be pinned or false if it should be unpinned
     * @return void
     */
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