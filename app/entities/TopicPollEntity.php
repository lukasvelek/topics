<?php

namespace App\Entities;

class TopicPollEntity implements ICreatableFromRow {
    private int $id;
    private string $title;
    private string $description;
    private int $authorId;
    private int $topicId;
    private array $choices;
    private string $dateCreated;
    private ?string $dateValid;
    private string $timeElapsedForNextVote;

    public function __construct(int $id, string $title, string $description, int $authorId, int $topicId, array $choices, string $dateCreated, ?string $dateValid, string $timeElapsedForNextVote) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->authorId = $authorId;
        $this->topicId = $topicId;
        $this->choices = $choices;
        $this->dateCreated = $dateCreated;
        $this->dateValid = $dateValid;
        $this->timeElapsedForNextVote = $timeElapsedForNextVote;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getAuthorId() {
        return $this->authorId;
    }

    public function getTopicId() {
        return $this->topicId;
    }

    public function getChoices() {
        return $this->choices;
    }

    public function getDateCreated() {
        return $this->dateCreated;
    }

    public function getDateValid() {
        return $this->dateValid;
    }

    public function getTimeElapsedForNextVote() {
        return $this->timeElapsedForNextVote;
    }

    public static function createEntityFromDbRow(mixed $row) {
        $choices = unserialize($row['choices']);
        
        return new self($row['pollId'], $row['title'], $row['description'], $row['authorId'], $row['topicId'], $choices, $row['dateCreated'], $row['dateValid'], $row['timeElapsedForNextVote']);
    }
}

?>