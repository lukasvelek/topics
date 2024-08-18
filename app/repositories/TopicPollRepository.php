<?php

namespace App\Repositories;

use App\Core\DatabaseConnection;
use App\Core\Datetypes\DateTime;
use App\Entities\TopicPollChoiceEntity;
use App\Entities\TopicPollEntity;
use App\Logger\Logger;
use App\UI\PollBuilder\PollBuilder;

class TopicPollRepository extends ARepository {
    public function __construct(DatabaseConnection $db, Logger $logger) {
        parent::__construct($db, $logger);
    }

    public function createPoll(string $pollId, string $title, string $description, string $authorId, string $topicId, string $choices, ?string $dateValid, string $timeElapsed) {
        $keys = ['pollId', 'title', 'description', 'authorId', 'topicId', 'choices', 'timeElapsedForNextVote'];
        $values = [$pollId, $title, $description, $authorId, $topicId, $choices, $timeElapsed];

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

    public function getActivePollBuilderEntitiesForTopic(string $topicId) {
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

    public function submitPoll(string $responseId, string $pollId, string $userId, int $choice) {
        $qb = $this->qb(__METHOD__);

        $qb ->insert('topic_polls_responses', ['responseId', 'pollId', 'userId', 'choice'])
            ->values([$responseId, $pollId, $userId, $choice])
            ->execute();

        return $qb->fetchBool();
    }

    public function getPollChoice(string $pollId, string $userId, ?string $dateLimit) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls_responses')
            ->where('pollId = ?', [$pollId])
            ->andWhere('userId = ?', [$userId]);

        if($dateLimit !== null) {
            $qb->andWhere('dateCreated > ?', [$dateLimit]);
        }

        $qb->execute();

        return TopicPollChoiceEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getPollRowById(string $pollId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls')
            ->where('pollId = ?', [$pollId])
            ->execute();

        return $qb->fetchAll();
    }

    public function getPollResponses(string $pollId) {
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

    public function getPollResponsesGrouped(string $pollId) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['COUNT(responseId) AS cnt', 'choice'])
            ->from('topic_polls_responses')
            ->where('pollId = ?', [$pollId])
            ->groupBy('choice')
            ->execute();

        $result = [];
        while($row = $qb->fetchAssoc()) {
            $result[$row['choice']] = $row['cnt'];
        }

        return $result;
    }

    public function closePoll(string $pollId) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topic_polls')
            ->set(['dateValid' => 'current_timestamp()'])
            ->where('pollId = ?', [$pollId])
            ->execute();

        return $qb->fetchBool();
    }

    public function openPoll(string $pollId, ?string $dateValid) {
        $qb = $this->qb(__METHOD__);

        $qb ->update('topic_polls')
            ->set(['dateValid' => $dateValid])
            ->where('pollId = ?', [$pollId])
            ->execute();

        return $qb->fetchBool();
    }

    public function getPollsForTopicForGrid(string $topicId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls')
            ->where('topicId = ?', [$topicId])
            ->orderBy('pollId', 'DESC');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $polls = [];
        while($row = $qb->fetchAssoc()) {
            $polls[] = TopicPollEntity::createEntityFromDbRow($row);
        }

        return $polls;
    }

    public function getPollById(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls')
            ->where('pollId = ?', [$id])
            ->execute();

        return TopicPollEntity::createEntityFromDbRow($qb->fetch());
    }

    public function getMyPollsForTopicForGrid(string $topicId, string $userId, int $limit, int $offset) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls')
            ->where('topicId = ?', [$topicId])
            ->andWhere('authorId = ?', [$userId])
            ->orderBy('pollId', 'DESC');

        $this->applyGridValuesToQb($qb, $limit, $offset);

        $qb->execute();

        $polls = [];
        while($row = $qb->fetchAssoc()) {
            $polls[] = TopicPollEntity::createEntityFromDbRow($row);
        }

        return $polls;
    }

    public function getPollCreatedByUserOrderedByDateDesc(string $userId, int $limit) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls')
            ->where('authorId = ?', [$userId])
            ->orderBy('dateCreated', 'DESC');

        if($limit > 0) {
            $qb->limit($limit);
        }

        $qb->execute();

        $polls = [];
        while($row = $qb->fetchAssoc()) {
            $polls[] = TopicPollEntity::createEntityFromDbRow($row);            
        }
    
        return $polls;
    }

    public function getPollResponsesForUserOrderedByDateDesc(string $userId, int $limit) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls_responses')
            ->where('userId = ?', [$userId])
            ->orderBy('dateCreated', 'DESC');

        if($limit > 0) {
            $qb->limit($limit);
        }

        $qb->execute();

        $choices = [];
        while($row = $qb->fetchAssoc()) {
            $choices[] = TopicPollChoiceEntity::createEntityFromDbRow($row);
        }

        return $choices;
    }

    public function getPollResponseById(string $id) {
        $qb = $this->qb(__METHOD__);

        $qb ->select(['*'])
            ->from('topic_polls_responses')
            ->where('responseId = ?', [$id])
            ->execute();

        return TopicPollChoiceEntity::createEntityFromDbRow($qb->fetch());
    }
}

?>