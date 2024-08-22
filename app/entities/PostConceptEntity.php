<?php

namespace App\Entities;

class PostConceptEntity {
    private string $conceptId;
    private string $authorId;
    private string $topicId;
    private string $postData;
    private string $dateCreated;

    public function __construct(string $conceptId, string $authorId, string $topicId, string $postData, string $dateCreated) {
        $this->conceptId = $conceptId;
        $this->authorId = $authorId;
        $this->topicId = $topicId;
        $this->postData = $postData;
        $this->dateCreated = $dateCreated;
    }

    public function getConceptId() {
        return $this->conceptId;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getPostData(bool $unserialize = true) {
        if($unserialize) {
            return unserialize($this->postData);
        } else {
            return $this->postData;
        }
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }
}

?>