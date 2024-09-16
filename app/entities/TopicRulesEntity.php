<?php

namespace App\Entities;

class TopicRulesEntity extends AEntity {
    private string $rulesetId;
    private string $topicId;
    private array $rules;
    private string $lastUpdateUserId;
    private string $dateCreated;
    private ?string $dateUpdated;

    public function __construct(string $rulesetId, string $topicId, array $rules, string $lastUpdateUserId, string $dateCreated, ?string $dateUpdated) {
        $this->rulesetId = $rulesetId;
        $this->topicId = $topicId;
        $this->rules = $rules;
        $this->lastUpdateUserId = $lastUpdateUserId;
        $this->dateCreated = $dateCreated;
        $this->dateUpdated = $dateUpdated;
    }

    public function getId() {
        return $this->rulesetId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getRules() {
        return $this->rules;
    }

    public function getLastUpdateUserId() {
        return $this->lastUpdateUserId;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getDateUpdated() {
        return $this->dateUpdated;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }
        $rules = json_decode($row['rules']);
        return new self($row['rulesetId'], $row['topicId'], $rules, $row['lastUpdateUserId'], $row['dateCreated'], $row['dateUpdated']);
    }
}

?>