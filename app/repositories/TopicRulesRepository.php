<?php

namespace App\Repositories;

use App\Core\Caching\CacheNames;
use App\Core\Caching\Cache;
use App\Core\DatabaseConnection;
use App\Entities\TopicRulesEntity;
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

    public function getTopicRulesForTopicId(string $topicId): TopicRulesEntity|null {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_rules')
            ->where('topicId = ?', [$topicId]);

        return $this->topicRulesCache->load($topicId, function() use ($qb) {
            $qb->execute();

            return TopicRulesEntity::createEntityFromDbRow($qb->fetch());
        });
    }

    public function insertTopicRules(string $rulesetId, string $topicId, string $rulesJson, string $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_rules', ['rulesetId', 'topicId', 'rules', 'lastUpdateUserId'])
            ->values([$rulesetId, $topicId, $rulesJson, $userId])
            ->execute();

        return $qb->fetchBool();
    }

    public function updateTopicRules(string $topicId, array $data) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topic_rules')
            ->set($data)
            ->where('topicId = ?', [$topicId])
            ->execute();
        
        return $qb->fetchBool();
    }
}

?>