<?php

namespace App\UI\PollBuilder;

use App\Core\Datetypes\DateTime;
use App\Helpers\DateTimeFormatHelper;
use App\UI\FormBuilder\FormBuilder;
use App\UI\IRenderable;
use App\UI\LinkBuilder;

class PollBuilder implements IRenderable {
    private array $choices;
    private string $title;
    private string $description;
    private array $handlerUrl;
    private ?string $pollId;
    private ?int $userChoice;
    private string $managerId;
    private ?string $currentUserId;
    private ?string $topicId;
    private ?string $timeNeededToElapse;
    private bool $canUserSeeAnalyticsAllTheTime;
    private ?string $userChoiceDate;

    public function __construct() {
        $this->choices = [];
        $this->title = 'My poll';
        $this->description = 'Description of my poll';
        $this->handlerUrl = [];
        $this->pollId = null;
        $this->userChoice = null;
        $this->managerId = 1;
        $this->currentUserId = null;
        $this->topicId = null;
        $this->timeNeededToElapse = null;
        $this->canUserSeeAnalyticsAllTheTime = false;
        $this->userChoiceDate = null;
    }

    public function setUserCanSeeAnalyticsAllTheTime(bool $canUserSeeAnalyticsAllTheTime = true) {
        $this->canUserSeeAnalyticsAllTheTime = $canUserSeeAnalyticsAllTheTime;
    }

    public function setTimeNeededToElapse(string $timeNeededToElapse) {
        $this->timeNeededToElapse = $timeNeededToElapse;
    }

    public function setTitle(string $title) {
        $this->title = $title;
        return $this;
    }

    public function setDescription(string $description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Choice value => choice text
     * 
     * E.g.: ['pizza' => 'Pizza', 'spaghetti' => 'Spaghetti']
     */
    public function setChoices(array $choices) {
        $this->choices = $choices;
        return $this;
    }

    public function setHandlerUrl(array $handlerUrl) {
        $this->handlerUrl = $handlerUrl;

        return $this;
    }

    public function setPollId(string $id) {
        $this->pollId = $id;

        return $this;
    }

    public function getId() {
        return $this->pollId;
    }
    
    public function setUserChoice(int $choice) {
        $this->userChoice = $choice;

        return $this;
    }

    public function setUserChoiceDate(string $date) {
        $this->userChoiceDate = $date;

        return $this;
    }

    public function setCurrentUserId(string $userId) {
        $this->currentUserId = $userId;

        return $this;
    }

    public function setManagerId(string $managerId) {
        $this->managerId = $managerId;

        return $this;
    }

    public function setTopicId(string $topicId) {
        $this->topicId = $topicId;

        return $this;
    }

    public function render() {
        $form = $this->build();

        $management = '';

        if(($this->currentUserId !== null && $this->currentUserId == $this->managerId) || $this->canUserSeeAnalyticsAllTheTime) {
            $analyticsLink = LinkBuilder::createSimpleLink('Results', ['page' => 'UserModule:Topics', 'action' => 'pollAnalytics', 'pollId' => $this->pollId], 'post-data-link');
            $closeVotingLink = LinkBuilder::createSimpleLink('Close voting', ['page' => 'UserModule:Topics', 'action' => 'pollCloseVoting', 'pollId' => $this->pollId, 'topicId' => $this->topicId], 'post-data-link');

            $management = '
                <div class="row">
                    <div class="col-md" id="left">
                        ' . $analyticsLink . '&nbsp;&nbsp;' . $closeVotingLink . '
                    </div>
                </div>
            ';
        }

        $code = '
            <div class="row" id="poll-id-' . $this->pollId . '">
                <div class="col-md">
                    <div class="row">
                        <div class="col-md" id="center">
                            <p class="post-title">' . $this->title . '</p>
                            <p class="post-data">' . $this->description . '</p>
                        </div>
                    </div>
                    ' . $management . '
                    <div class="row">
                        <div class="col-md" id="form">
                            <div id="poll-choice-form">
                                ' . $form . '
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ';

        return $code;
    }

    private function build() {
        $fb = new FormBuilder();

        $availableChoice = [
            $this->userChoice => $this->choices[$this->userChoice]
        ];

        $fb ->setAction($this->handlerUrl)
            ->setMethod('POST')
            ->addRadios('choice', 'Choose:', $availableChoice, $this->userChoice, true)
        ;

        if($this->userChoice !== null) {
            if($this->timeNeededToElapse !== null && $this->timeNeededToElapse != '0' && $this->userChoiceDate !== null) {
                $timeNeededToElapse = $this->timeNeededToElapse;
                $timeNeededToElapse[0] = '+';

                $dt = new DateTime(strtotime($this->userChoiceDate));
                $dt->modify($timeNeededToElapse);
                $dt = DateTimeFormatHelper::formatDateToUserFriendly($dt->getResult());

                $fb->addLabel('You have to wait until ' . $dt . ' before another vote.', 'lbl_message1');
            } else if($this->timeNeededToElapse !== null && $this->timeNeededToElapse == '0') {
                $fb->addLabel('You can vote only once.', 'lbl_message1');
            } else {
                $fb->addLabel('You have to wait before another vote.', 'lbl_message1');
            }
        }

        if($this->userChoice !== null) {

        } else {
            $fb->addSubmit('Submit', ($this->userChoice !== null), true);
        }

        return $fb->render();
    }

    public static function createFromDbRow(mixed $row) {
        $pb = new self();

        $pb->setTitle($row['title']);
        $pb->setDescription($row['description']);
        $pb->setChoices(unserialize($row['choices']));
        $url = ['page' => 'UserModule:Topics', 'action' => 'pollSubmit', 'topicId' => $row['topicId'], 'pollId' => $row['pollId']];
        $pb->setHandlerUrl($url);
        $pb->setPollId($row['pollId']);
        $pb->setManagerId($row['authorId']);
        $pb->setTopicId($row['topicId']);

        return $pb;
    }
}

?>