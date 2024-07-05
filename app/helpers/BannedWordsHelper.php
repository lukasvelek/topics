<?php

namespace App\Helpers;

use App\Repositories\ContentRegulationRepository;

class BannedWordsHelper {
    private ContentRegulationRepository $crr;
    private array $bannedWordsList;
    private bool $isListFilled;

    public function __construct(ContentRegulationRepository $crr) {
        $this->crr = $crr;
        $this->bannedWordsList = [];
        $this->isListFilled = false;
    }

    public function checkText(string $text) {
        if(!$this->isListFilled) {
            $this->bannedWordsList = $this->getBannedWordList();
            $this->isListFilled = true;
        }

        $escapedWords = array_map('preg_quote', $this->bannedWordsList);

        $pattern = '/\b(' . implode('|', $escapedWords) . ')\b/i';

        $callback = function($matches) {
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
}

?>