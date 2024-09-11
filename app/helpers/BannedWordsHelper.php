<?php

namespace App\Helpers;

use App\Repositories\ContentRegulationRepository;
use App\Repositories\TopicContentRegulationRepository;

class BannedWordsHelper {
    private ContentRegulationRepository $crr;
    private ?TopicContentRegulationRepository $tcrr;
    private array $bannedWordsList;
    private bool $isListFilled;
    private array $bannedWordsUsed;

    public function __construct(ContentRegulationRepository $crr, ?TopicContentRegulationRepository $tcrr = null) {
        $this->crr = $crr;
        $this->tcrr = $tcrr;
        $this->bannedWordsList = [];
        $this->isListFilled = false;
        $this->bannedWordsUsed = [];
    }

    public function checkText(string $text, ?string $topicId = null) {
        if(!$this->isListFilled) {
            $this->bannedWordsList = $this->getBannedWordList();
            $this->isListFilled = true;
        }

        $bannedWordsList = $this->bannedWordsList;

        if($topicId !== null && $this->tcrr !== null) {
            $topicBannedWords = $this->getTopicBannedWordList($topicId);
            $bannedWordsList = array_merge($bannedWordsList, $topicBannedWords);
        }

        $escapedWords = array_map('preg_quote', $bannedWordsList);

        $pattern = '/\b(' . implode('|', $escapedWords) . ')\b/i';

        $callback = function($matches) {
            $this->bannedWordsUsed[] = $matches[0];
            return str_repeat('*', strlen($matches[0]));
        };

        $text = preg_replace_callback($pattern, $callback, $text);

        return $text;
    }

    private function getBannedWordList() {
        $bannedWords = $this->crr->getBannedWordsForGrid(0, 0);

        $tmp = [];
        foreach($bannedWords as $bw) {
            $tmp[] = $bw->getText();
        }

        return $tmp;
    }

    private function getTopicBannedWordList(string $topicId) {
        $bannedWords = $this->tcrr->getBannedWordsForTopicForGrid($topicId, 0, 0);

        $tmp = [];
        foreach($bannedWords as $bw) {
            $tmp[] = $bw->getText();
        }

        return $tmp;
    }

    public function getBannedWordsUsed() {
        return $this->bannedWordsUsed;
    }

    public function cleanBannedWordsUsed() {
        $this->bannedWordsUsed = [];
    }
}

?>