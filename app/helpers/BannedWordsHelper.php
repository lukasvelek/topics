<?php

namespace App\Helpers;

use App\Repositories\ContentRegulationRepository;
use App\Repositories\TopicContentRegulationRepository;

/**
 * BannedWordsHelper contains useful functions for banned words
 * 
 * @author Lukas Velek
 */
class BannedWordsHelper {
    private ContentRegulationRepository $crr;
    private ?TopicContentRegulationRepository $tcrr;
    private array $bannedWordsList;
    private bool $isListFilled;
    private array $bannedWordsUsed;

    /**
     * Class constructor
     * 
     * @param ContentRegulationReposiory $crr ContentRegulationRepository instance
     * @param null|TopicContentRegulationRepository $tcrr TopicContentRegulationRepository instance or null
     */
    public function __construct(ContentRegulationRepository $crr, ?TopicContentRegulationRepository $tcrr = null) {
        $this->crr = $crr;
        $this->tcrr = $tcrr;
        $this->bannedWordsList = [];
        $this->isListFilled = false;
        $this->bannedWordsUsed = [];
    }

    /**
     * Checks given text if it contains globally banned words and/or topic banned words
     * 
     * @param string $text Text to check
     * @param null|string $topicId Topic ID
     * @return string Checked text
     */
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
            if(!empty($matches[0])) {
                $this->bannedWordsUsed[] = $matches[0];
            }
            return str_repeat('*', strlen($matches[0]));
        };

        $text = preg_replace_callback($pattern, $callback, $text);

        return $text;
    }

    /**
     * Returns a list of globally banned words
     * 
     * @return array Globally banned words
     */
    private function getBannedWordList() {
        $bannedWords = $this->crr->getBannedWordsForGrid(0, 0);

        $tmp = [];
        foreach($bannedWords as $bw) {
            $tmp[] = $bw->getText();
        }

        return $tmp;
    }

    /**
     * Returns a list of topic banned words
     * 
     * @param string $topicId Topic ID
     * @return array Topic banned words
     */
    private function getTopicBannedWordList(string $topicId) {
        $bannedWords = $this->tcrr->getBannedWordsForTopicForGrid($topicId, 0, 0);

        $tmp = [];
        foreach($bannedWords as $bw) {
            $tmp[] = $bw->getText();
        }

        return $tmp;
    }

    /**
     * Returns the list of banned words that have been used when checking text
     * 
     * @return array Used banned words
     */
    public function getBannedWordsUsed() {
        return $this->bannedWordsUsed;
    }

    /**
     * Cleans the list of banned words that have been used when checking text
     */
    public function cleanBannedWordsUsed() {
        $this->bannedWordsUsed = [];
    }
}

?>