<?php

namespace App\Managers;

use App\Authorizators\VisibilityAuthorizator;
use App\Constants\TopicMemberRole;
use App\Core\CacheManager;
use App\Entities\TopicEntity;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\TopicVisibilityException;
use App\Logger\Logger;
use App\Repositories\TopicRepository;

class TopicManager extends AManager {
    private const CACHE_NAMESPACE = 'topics';

    private TopicRepository $tr;
    private CacheManager $cm;
    private TopicMembershipManager $tmm;
    private VisibilityAuthorizator $va;

    public function __construct(Logger $logger, TopicRepository $topicRepository, TopicMembershipManager $tmm, VisibilityAuthorizator $va) {
        parent::__construct($logger);
        
        $this->tr = $topicRepository;
        $this->tmm = $tmm;
        $this->va = $va;
        $this->cm = new CacheManager($logger);
    }

    public function getTopicById(int $topicId, int $userId) {
        $topic = $this->tr->getTopicById($topicId);

        if($topic === null) {
            throw new TopicVisibilityException('No topic with ID #' . $topicId . ' exists.');
        }

        $this->checkPrivacy($topic, $userId);

        return $topic;
    }

    private function isUserMember(int $topicId, int $userId) {
        return $this->tmm->checkFollow($topicId, $userId);
    }

    private function checkPrivacy(TopicEntity $topic, int $userId) {
        if($topic->isPrivate()) {
            if(!$this->isUserMember($topic->getId(), $userId)) {
                if(!$this->va->canViewPrivateTopic($userId)) {
                    throw new TopicVisibilityException('You are not allowed to view private topics.');
                }
            }
        }
    }

    public function getTopicsByIdArray(array $topicIds, int $userId) {
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

    public function getTopicsNotInIdArray(array $topicIds, int $userId) {
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

    public function checkTopicsVisibility(array $topics, int $userId) {
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

    public function isUserOwner(int $topicId, int $userId) {
        return ($this->tmm->getFollowRole($topicId, $userId) == TopicMemberRole::OWNER);
    }
}

?>