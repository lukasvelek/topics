<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicPollEntity;
use App\Logger\Logger;
use App\UI\PollBuilder\PollBuilder;

class TopicPollRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createPoll(string $title, string $description, int $authorId, int $topicId, string $choices, ?string $dateValid) {
        $keys = ['title', 'description', 'authorId', 'topicId', 'choices'];
        $values = [$title, $description, $authorId, $topicId, $choices];

        if($dateValid !== null) {
            $keys[] = 'dateValid';
            $values[] = $dateValid;
        }

        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_polls', $keys)
            ->values($values)
            ->execute();

        return $qb->fetchBool();
    }

    public function getActivePollBuilderEntitiesForTopic(int $topicId) {
        $now = new DateTime();
        $now = $now->getResult();

        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls')
            ->where('topicId = ?', [$topicId])
            ->andWhere('(dateValid IS NULL')
            ->orWhere('dateValid >= ?)', [$now])
            ->execute();

        $polls = [];
        while($row = $qb->fetchAssoc()) {
            $polls[] = PollBuilder::createFromDbRow($row);
        }

        return $polls;
    }

    public function submitPoll(int $pollId, int $userId, int $choice) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_polls_responses', ['pollId', 'userId', 'choice'])
            ->values([$pollId, $userId, $choice])
            ->execute();

        return $qb->fetchBool();
    }

    public function getPollChoice(int $pollId, int $userId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['choice'])
            ->from('topic_polls_responses')
            ->where('pollId = ?', [$pollId])
            ->andWhere('userId = ?', [$userId])
            ->execute();

        return $qb->fetch('choice');
    }

    public function getPollRowById(int $pollId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls')
            ->where('pollId = ?', [$pollId])
            ->execute();

        return $qb->fetchAll();
    }

    public function getPollResponses(int $pollId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['choice'])
            ->from('topic_polls_responses')
            ->where('pollId = ?', [$pollId])
            ->execute();

        $choices = [];
        while($row = $qb->fetchAssoc()) {
            $choices[] = $row['choice'];
        }

        return $choices;
    }

    public function closePoll(int $pollId) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topic_polls')
            ->set(['dateValid' => 'current_timestamp()'])
            ->where('pollId = ?', [$pollId])
            ->execute();

        return $qb->fetchBool();
    }

    public function openPoll(int $pollId) {
        $qb = $this->qb(__METHOD__);

        $tomorrow = new DateTime();
        $tomorrow->modify('+1d');
        $tomorrow = $tomorrow->getResult();

        $qb ->update('topic_polls')
            ->set(['dateValid' => $tomorrow])
            ->where('pollId = ?', [$pollId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getPollsForTopicForGrid(int $topicId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls')
            ->where('topicId = ?', [$topicId])
            ->orderBy('pollId', 'DESC');

        if($limit > 0) {
            $qb->limit($limit);
        }
        if($offset > 0) {
            $qb->offset($offset);
        }

        $qb->execute();

        $polls = [];
        while($row = $qb->fetchAssoc()) {
            $polls[] = TopicPollEntity::createEntityFromDbRow($row);
        }

        return $polls;
    }
}

?>