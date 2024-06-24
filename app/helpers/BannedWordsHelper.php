<?php

namespace App\Helpers;

use App\Repositories\ContentRegulationRepository;

class BannedWordsHelper {
    private ContentRegulationRepository $crr;

    public function __construct(ContentRegulationRepository $crr) {
        $this->crr = $crr;
    }

    public function checkText(string $text) {
        $words = $this->getBannedWordList();

        $escapedWords = array_map('preg_quote', $words);

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