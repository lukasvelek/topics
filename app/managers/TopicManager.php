<?php

namespace App\Managers;

use App\Authorizators\VisibilityAuthorizator;
use App\Constants\TopicMemberRole;
use App\Core\CacheManager;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicEntity;
use App\Exceptions\AException;
use App\Exceptions\DatabaseExecutionException;
use App\Exceptions\GeneralException;
use App\Exceptions\TopicVisibilityException;
use App\Logger\Logger;
use App\Repositories\TopicRepository;
use App\Repositories\TopicRulesRepository;
use Exception;

class TopicManager extends AManager {
    private TopicRepository $tr;
    private TopicMembershipManager $tmm;
    private VisibilityAuthorizator $va;
    private ContentManager $com;
    private TopicRulesRepository $trr;

    public function __construct(Logger $logger, TopicRepository $topicRepository, TopicMembershipManager $tmm, VisibilityAuthorizator $va, ContentManager $com, EntityManager $entityManager, TopicRulesRepository $trr) {
        parent::__construct($logger, $entityManager);
        
        $this->tr = $topicRepository;
        $this->tmm = $tmm;
        $this->va = $va;
        $this->com = $com;
        $this->trr = $trr;
    }

    public function getTopicById(string $topicId, string $userId) {
        $topic = $this->tr->getTopicById($topicId);

        if($topic === null) {
            throw new TopicVisibilityException('No topic with ID #' . $topicId . ' exists.');
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

        $members = $this->tmm->getTopicMembers($topicId, 0, 0, false);

        foreach($members as $member) {
            $memberId = $member->getUserId();

            $this->tmm->unfollowTopic($topicId, $memberId);
        }
    }

    public function updateTopicPrivacy(string $userId, string $topicId, bool $isPrivate, bool $isVisible) {
        if($this->tmm->getFollowRole($topicId, $userId) < TopicMemberRole::OWNER) {
            throw new GeneralException('You are not authorized to change these settings.');
        }

        try {
            $this->com->updateTopic($topicId, ['isPrivate' => $isPrivate, 'isVisible' => $isVisible]);

            $this->cache->invalidateCache(CacheManager::NS_TOPICS);
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

            $this->cache->invalidateCache(CacheManager::NS_PINNED_POSTS);
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

            $this->cache->invalidateCache(CacheManager::NS_PINNED_POSTS);
        } catch(AException $e) {
            throw $e;
        }

        return true;
    }

    public function isPostPinned(string $topicId, string $postId) {
        $pinnedPostIds = $this->tr->getPinnedPostIdsForTopicId($topicId);

        return in_array($postId, $pinnedPostIds);
    }

    private function getTopicRulesEntityForTopicId(string $topicId) {
        return $this->trr->getTopicRulesForTopicId($topicId);
    }

    public function getTopicRulesForTopicId(string $topicId) {
        $entity = $this->getTopicRulesEntityForTopicId($topicId);

        if($entity === null) {
            return [];
        }

        return $entity->getRules();
    }

    public function hasTopicRules(string $topicId) {
        $rules = $this->getTopicRulesForTopicId($topicId);

        return !empty($rules);
    }

    public function addRuleTextToTopicRules(string $topicId, string $ruleText, string $userId) {
        $entity = $this->getTopicRulesEntityForTopicId($topicId);

        try {
            if($entity === null) {
                // create new
    
                $rulesetId = $this->trr->createEntityId(EntityManager::TOPIC_RULES);
    
                $rulesJson = json_encode([$ruleText]);
    
                if(!$this->trr->insertTopicRules($rulesetId, $topicId, $rulesJson, $userId)) {
                    throw new GeneralException('Could not insert new topic rules.');
                }
            } else {
                // update
    
                $rules = $entity->getRules();
                $rules[] = $ruleText;
                $rulesJson = json_encode($rules);
    
                if(!$this->trr->updateTopicRules($topicId, ['rules' => $rulesJson, 'lastUpdateUserId' => $userId, 'dateUpdated' => DateTime::now()])) {
                    throw new GeneralException('Could not update topic rules.');
                }
            }

            if(!$this->cache->invalidateCache(CacheManager::NS_TOPIC_RULES)) {
                throw new GeneralException('Could not invalidate cache.');
            }
        } catch(AException $e) {
            throw $e;
        }
    }
}

?>