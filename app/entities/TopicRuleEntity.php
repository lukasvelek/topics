<?php

namespace App\Entities;

class TopicRuleEntity extends AEntity {
    private string $ruleId;
    private string $topicId;
    private string $ruleText;
    private string $lastUpdateUserId;
    private string $dateCreated;
    private ?string $dateUpdated;

    public function __construct(string $ruleId, string $topicId, string $ruleText, string $lastUpdateUserId, string $dateCreated, ?string $dateUpdated) {
        $this->ruleId = $ruleId;
        $this->topicId = $topicId;
        $this->ruleText = $ruleText;
        $this->lastUpdateUserId = $lastUpdateUserId;
        $this->dateCreated = $dateCreated;
        $this->dateUpdated = $dateUpdated;
    }

    public function getId() {
        return $this->ruleId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getText() {
        return $this->ruleText;
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

        $row = self::createRow($row);
        self::checkTypes($row, ['ruleId' => 'string', 'topicId' => 'string', 'ruleText' => 'string', 'lastUpdateUserId' => 'string', 'dateCreated' => 'string', 'dateUpdated' => '?string']);

        return new self($row->ruleId, $row->topicId, $row->ruleText, $row->lastUpdateUserId, $row->dateCreated, $row->dateUpdated);
    }
}

?>