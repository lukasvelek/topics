<?php

namespace App\Services;

use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\PostManager;
use App\Managers\TrendsManager;
use Exception;

class HashtagTrendsIndexingService extends AService {
    private const MAX_COUNT = 1000;
    private const SAVE_MAX_COUNT = 5;

    private PostManager $pm;
    private TrendsManager $tm;

    public function __construct(Logger $logger, ServiceManager $serviceManager, PostManager $pm, TrendsManager $tm) {
        parent::__construct('HashtagTrendsIndexing', $logger, $serviceManager);

        $this->pm = $pm;
        $this->tm = $tm;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $usages = [];

        $offset = 0;
        $x = 0;
        $maxRuns = 1000;
        while(true) {
            if($x >= $maxRuns) {
                break;
            }

            $hashtags = $this->pm->getListOfPostsWithHashtagsInDescriptions((self::MAX_COUNT + 1), $offset);

            foreach($hashtags as $hashtag) {
                if(!array_key_exists($hashtag, $usages)) {
                    $usages[$hashtag] = 1;
                } else {
                    $usages[$hashtag] = $usages[$hashtag] + 1;
                }
            }

            arsort($usages, SORT_NUMERIC);

            if(count($hashtags) > self::MAX_COUNT) {
                $offset += self::MAX_COUNT;
            }
            if(count($hashtags) < self::MAX_COUNT) {
                break;
            }

            $x++;
        }

        $tmp = [];
        $keys = array_keys($usages);
        for($i = 0; $i < self::SAVE_MAX_COUNT; $i++) {
            if(count($keys) <= $i) {
                break;
            }

            $tmp[$keys[$i]] = $usages[$keys[$i]];
        }
        
        $usages = $tmp;

        try {
            $this->tm->createNewHashtagTrendsEntry(serialize($usages));
        } catch(AException $e) {
            throw $e;
        }
    }
}

?>