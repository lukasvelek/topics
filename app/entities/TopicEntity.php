<?php

namespace App\Entities;

use App\UI\LinkBuilder;

class TopicEntity extends AEntity {
    private string $topicId;
    private string $title;
    private string $description;
    private string $dateCreated;
    private bool $isDeleted;
    private ?string $dateDeleted;
    private array $tags;
    private bool $private;
    private bool $visible;
    private array $rawTags;

    public function __construct(string $topicId, string $title, string $description, string $dateCreated, bool $isDeleted, ?string $dateDeleted, array $tags, bool $private, bool $visible, array $rawTags) {
        $this->topicId = $topicId;
        $this->title = $title;
        $this->description = $description;
        $this->dateCreated = $dateCreated;
        $this->isDeleted = $isDeleted;
        $this->dateDeleted = $dateDeleted;
        $this->tags = $tags;
        $this->private = $private;
        $this->visible = $visible;
        $this->rawTags = $rawTags;
    }

    public function getId() {
        return $this->topicId;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }
    
    public function isDeleted() {
        return $this->isDeleted;
    }

    public function getDateDeleted() {
        return $this->dateDeleted;
    }

    public function getTags() {
        return $this->tags;
    }

    public function isPrivate() {
        return $this->private;
    }

    public function isVisible() {
        return $this->visible;
    }

    public function getRawTags() {
        return $this->rawTags;
    }

    public static function createEntityFromDbRow(mixed $row) {
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        $row->tags = unserialize($row->tags);
        $row->rawTags = explode(',', $row->rawTags);
        self::checkTypes($row, ['topicId' => 'string', 'title' => 'string', 'description' => 'string', 'dateCreated' => 'string', 'isDeleted' => 'bool', 'dateDeleted' => '?string', 'tags' => 'array', 'isPrivate' => 'bool',
                                'isVisible' => 'bool', 'rawTags' => 'array']);

        return new self($row->topicId, $row->title, $row->description, $row->dateCreated, $row->isDeleted, $row->dateDeleted, $row->tags, $row->isPrivate, $row->isVisible, $row->rawTags);
    }

    public static function createTopicProfileLink(TopicEntity $topic, bool $object = false, string $class = 'post-data-link') {
        if($object) {
            return LinkBuilder::createSimpleLinkObject($topic->getTitle(), ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topic->getId()], $class);
        } else {
            return LinkBuilder::createSimpleLink($topic->getTitle(), ['page' => 'UserModule:Topics', 'action' => 'profile', 'topicId' => $topic->getId()], $class);
        }
    }
}

?>