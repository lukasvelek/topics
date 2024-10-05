<?php

namespace App\Entities;

class TopicPollEntity extends AEntity {
    private string $id;
    private string $title;
    private string $description;
    private string $authorId;
    private string $topicId;
    private array $choices;
    private string $dateCreated;
    private ?string $dateValid;
    private string $timeElapsedForNextVote;

    public function __construct(string $id, string $title, string $description, string $authorId, string $topicId, array $choices, string $dateCreated, ?string $dateValid, string $timeElapsedForNextVote) {
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
        if($row === null) {
            return null;
        }

        $row = self::createRow($row);
        $row->choices = unserialize($row->choices);
        self::checkTypes($row, ['pollId' => 'string', 'title' => 'string', 'description' => 'string', 'authorId' => 'string', 'topicId' => 'string', 'choices' => 'array', 'dateCreated' => 'string',
                                'dateValid' => '?string', 'timeElapsedForNextVote' => 'string']);

        return new self($row->pollId, $row->title, $row->description, $row->authorId, $row->topicId, $row->choices, $row->dateCreated, $row->dateValid, $row->timeElapsedForNextVote);
    }
}

?>