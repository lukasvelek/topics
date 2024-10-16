<?php

namespace App\Repositories;

use App\Core\Caching\CacheNames;
use App\Core\Caching\Cache;
use App\Core\DatabaseConnection;
use App\Entities\TopicRuleEntity;
use App\Logger\Logger;

class TopicRulesRepository extends ARepository {
    private Cache $topicRulesCache;

    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);

        $this->topicRulesCache = $this->cacheFactory->getCache(CacheNames::TOPIC_RULES);
    }

    public function composeQueryForTopicRulesForTopicId(string $topicId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_rules')
            ->where('topicId = ?', [$topicId]);

        return $qb;
    }

    public function getTopicRulesForTopicId(string $topicId): array {
        $qb = $this->composeQueryForTopicRulesForTopicId($topicId);

        $qb->execute();

        $entities = [];
        while($row = $qb->fetchAssoc()) {
            $entity = TopicRuleEntity::createEntityFromDbRow($row);

            if($entity !== null) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    public function createTopicRule(string $ruleId, string $topicId, string $ruleText, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_rules', ['ruleId', 'topicId', 'ruleText', 'lastUpdateUserId'])
            ->values([$ruleId, $topicId, $ruleText, $userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateTopicRules(string $ruleId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topic_rules')
            ->set($data)
            ->where('ruleId = ?', [$ruleId])
            ->execute();
        
        return $qb->fetchBool();
    }

    public function getTopicRuleById(string $ruleId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_rules')
            ->where('ruleId = ?', [$ruleId])
            ->execute();

        return TopicRuleEntity::createEntityFromDbRow($qb->fetch());
    }

    public function deleteTopicRule(string $ruleId) {
        return $this->deleteEntryById('topic_rules', 'ruleId', $ruleId);
    }
}

?>