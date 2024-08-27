<?php

namespace App\Repositories;

use App\Core\CacheManager;
use App\Core\DatabaseConnection;
use App\Entities\TopicRulesEntity;
use App\Logger\Logger;

class TopicRulesRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function getTopicRulesForTopicId(string $topicId): TopicRulesEntity|null {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_rules')
            ->where('topicId = ?', [$topicId]);

        return $this->cache->loadCache($topicId, function () use ($qb) {
            $qb->execute();

            return TopicRulesEntity::createEntityFromDbRow($qb->fetch());
        }, CacheManager::NS_TOPIC_RULES, __METHOD__);
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