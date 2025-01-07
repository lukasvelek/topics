<?php

namespace App\Managers;

use App\Authorizators\VisibilityAuthorizator;
use App\Constants\TopicMemberRole;
use App\Core\Caching\Cache;
use App\Core\Caching\CacheNames;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicEntity;
use App\Exceptions\AException;
use App\Exceptions\CacheException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Exceptions\TopicVisibilityException;
use App\Logger\Logger;
use App\Repositories\TopicCalendarEventRepository;
use App\Repositories\TopicContentRegulationRepository;
use App\Repositories\TopicRepository;
use App\Repositories\TopicRulesRepository;
use Exception;

class TopicManager extends AManager {
    private TopicRepository $tr;
    private TopicMembershipManager $tmm;
    private VisibilityAuthorizator $va;
    private ContentManager $com;
    private TopicRulesRepository $trr;
    private TopicContentRegulationRepository $tcrr;
    private TopicCalendarEventRepository $tcer;

    private Cache $pinnedPostsCache;

    public function __construct(Logger $logger, TopicRepository $topicRepository, TopicMembershipManager $tmm, VisibilityAuthorizator $va, ContentManager $com, EntityManager $entityManager, TopicRulesRepository $trr, TopicContentRegulationRepository $tcrr, TopicCalendarEventRepository $tcer) {
        parent::__construct($logger, $entityManager);
        
        $this->tr = $topicRepository;
        $this->tmm = $tmm;
        $this->va = $va;
        $this->com = $com;
        $this->trr = $trr;
        $this->tcrr = $tcrr;
        $this->tcer = $tcer;

        $this->pinnedPostsCache = $this->cacheFactory->getCache(CacheNames::PINNED_POSTS);
    }

    public function getTopicById(string $topicId, string $userId) {
        $topic = $this->tr->getTopicById($topicId);

        if($topic === null) {
            throw new NonExistingEntityException('Topic with ID #' . $topicId . ' does not exist.');
        }

        $this->checkPrivacy($topic, $userId);

        return $topic;
    }

    private function isUserMember(string $topicId, string $userId) {
        return $this->tmm->checkFollow($topicId, $userId);
    }

    private function checkPrivacy(TopicEntity $topic, string $userId) {
        if($topic->isPrivate() && !$topic->isVisible()) {
            if(!$this->isUserMember($topic->getId(), $userId)) {
                if(!$this->va->canViewPrivateTopic($userId)) {
                    throw new TopicVisibilityException('You are not allowed to view private topics.');
                }
            }
        }
    }

    public function getTopicsByIdArray(array $topicIds, string $userId) {
        $topics = [];

        foreach($topicIds as $topicId) {
            try {
                $topics[] = $this->getTopicById($topicId, $userId);
            } catch(AException $e) {
                continue;
            }
        }

        return $topics;
    }

    public function getTopicsNotInIdArray(array $topicIds, string $userId) {
        $topics = $this->tr->getTopicsExceptFor($topicIds);

        $returnableTopics = [];

        foreach($topics as $topic) {
            try {
                $this->checkPrivacy($topic, $userId);

                $returnableTopics[] = $topic;
            } catch(AException $e) {
                continue;
            }
        }

        return $returnableTopics;
    }

    public function checkTopicsVisibility(array $topics, string $userId) {
        $okTopics = [];

        foreach($topics as $topic) {
            try {
                $this->checkPrivacy($topic, $userId);

                $okTopics[] = $topic;
            } catch(AException $e) {
                continue;
            }
        }

        return $okTopics;
    }

    public function isUserOwner(string $topicId, string $userId) {
        return ($this->tmm->getFollowRole($topicId, $userId) == TopicMemberRole::OWNER);
    }

    public function deleteTopic(string $topicId, string $userId) {
        if($this->tmm->getFollowRole($topicId, $userId) < TopicMemberRole::OWNER) {
            throw new GeneralException('You are not authorized to delete this topic.');
        }

        $this->com->deleteTopic($topicId);

        // memberships
        $members = $this->tmm->getTopicMembers($topicId, 0, 0, false);

        foreach($members as $member) {
            $memberId = $member->getUserId();

            $this->tmm->unfollowTopic($topicId, $memberId);
        }

        // banned words
        $this->tcrr->deleteBannedWordsForTopicId($topicId);

        // user calendar events
        $this->tcer->deleteUserEventsForTopicId($topicId);
    }

    public function updateTopicPrivacy(string $userId, string $topicId, bool $isPrivate, bool $isVisible) {
        if($this->tmm->getFollowRole($topicId, $userId) < TopicMemberRole::OWNER) {
            throw new GeneralException('You are not authorized to change these settings.');
        }

        try {
            $this->com->updateTopic($topicId, ['isPrivate' => $isPrivate, 'isVisible' => $isVisible]);

            $topicsCache = $this->cacheFactory->getCache(CacheNames::TOPICS);
            $topicsCache->invalidate();
        } catch(Exception $e) {
            throw $e;
        }
    }

    public function pinPost(string $callingUserId, string $topicId, string $postId) {
        if($this->tmm->getFollowRole($topicId, $callingUserId) < TopicMemberRole::COMMUNITY_HELPER) {
            throw new GeneralException('You are not authorized to pin posts.');
        }

        if($this->isPostPinned($topicId, $postId)) {
            throw new GeneralException('Post is already pinned.');
        }

        try {
            $this->com->pinPost($topicId, $postId);

            if(!$this->cacheFactory->invalidateCacheByCache($this->pinnedPostsCache)) {
                throw new CacheException('Could not invalidate cache.', $this->pinnedPostsCache->getNamespace());
            }
        } catch(AException $e) {
            throw $e;
        }

        return true;
    }

    public function unpinPost(string $callingUserId, string $topicId, string $postId) {
        if($this->tmm->getFollowRole($topicId, $callingUserId) < TopicMemberRole::COMMUNITY_HELPER) {
            throw new GeneralException('You are not authorized to pin posts.');
        }

        if(!$this->isPostPinned($topicId, $postId)) {
            throw new GeneralException('Post is not pinned.');
        }

        try {
            $this->com->pinPost($topicId, $postId, false);

            //$this->pinnedPostsCache->invalidate();
            if(!$this->cacheFactory->invalidateCacheByCache($this->pinnedPostsCache)) {
                throw new CacheException('Could not invalidate cache.', $this->pinnedPostsCache->getNamespace());
            }
        } catch(AException $e) {
            throw $e;
        }

        return true;
    }

    public function isPostPinned(string $topicId, string $postId) {
        $pinnedPostIds = $this->tr->getPinnedPostIdsForTopicId($topicId);

        return in_array($postId, $pinnedPostIds);
    }

    private function getTopicRuleEntitiesForTopicId(string $topicId) {
        return $this->trr->getTopicRulesForTopicId($topicId);
    }

    public function composeQueryForTopicRules(string $topicId) {
        return $this->trr->composeQueryForTopicRulesForTopicId($topicId);
    }

    public function getTopicRuleById(string $ruleId) {
        $entity = $this->trr->getTopicRuleById($ruleId);

        if($entity === null) {
            throw new NonExistingEntityException('Could not find rule.');
        }

        return $entity;
    }

    public function getTopicRulesForTopicId(string $topicId) {
        return $this->getTopicRuleEntitiesForTopicId($topicId);
    }

    public function hasTopicRules(string $topicId) {
        $rules = $this->getTopicRulesForTopicId($topicId);

        return !empty($rules);
    }

    public function addRuleTextToTopicRules(string $topicId, string $ruleText, string $userId) {
        try {
            $ruleId = $this->trr->createEntityId(EntityManager::TOPIC_RULES);

            if(!$this->trr->createTopicRule($ruleId, $topicId, $ruleText, $userId)) {
                throw new GeneralException('Could not create new topic rule.');
            }
            
            $topicRulesCache = $this->cacheFactory->getCache(CacheNames::TOPIC_RULES);
            $topicRulesCache->invalidate();
        } catch(AException $e) {
            throw $e;
        }
    }

    public function updateTopicRule(string $userId, string $ruleId, string $newText) {
        try {
            if(!$this->trr->updateTopicRules($ruleId, ['ruleText' => $newText, 'lastUpdateUserId' => $userId, 'dateUpdated' => DateTime::now()])) {
                throw new GeneralException('Could not update topic rule.');
            }

            $topicRulesCache = $this->cacheFactory->getCache(CacheNames::TOPIC_RULES);
            $topicRulesCache->invalidate();
        } catch(AException $e) {
            throw $e;
        }
    }

    public function deleteTopicRule(string $ruleId, string $userId) {
        try {
            if(!$this->trr->deleteTopicRule($ruleId)) {
                throw new GeneralException('Could not update topic rule.');
            }
        } catch(AException $e) {
            throw $e;
        }
    }

    public function getTopicOwner(string $topicId) {
        return $this->tmm->getTopicOwnerId($topicId);
    }

    public function getFollowedTopics(string $userId, array &$topicIdsUserIsMemberOf) {
        $topicIdsUserIsMemberOf = $this->getTopicIdsUserIsMemberOf($userId);
        return $this->getTopicsByIdArray($topicIdsUserIsMemberOf, $userId);
    }

    public function getTopicIdsUserIsMemberOf(string $userId) {
        return $this->tmm->getUserMembershipsInTopics($userId);
    }
}

?>