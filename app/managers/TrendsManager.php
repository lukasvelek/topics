<?php

namespace App\Managers;

use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Exceptions\NonExistingEntityException;
use App\Logger\Logger;
use App\Repositories\HashtagTrendsRepository;

class TrendsManager extends AManager {
    private HashtagTrendsRepository $htr;
    
    public function __construct(Logger $logger, EntityManager $entityManager, HashtagTrendsRepository $htr) {
        parent::__construct($logger, $entityManager);
        
        $this->htr = $htr;
    }

    public function createNewHashtagTrendsEntry(string $data) {
        try {
            $this->htr->beginTransaction(__METHOD__);

            $entryId = $this->htr->createEntityId(EntityManager::HASHTAG_TRENDS);

            if($entryId === null) {
                throw new GeneralException('Could not generate unique entry ID.');
            }

            $result = $this->htr->createNewEntry($entryId, $data);

            if($result === false) {
                throw new GeneralException('Could not create new entry.');
            }

            $this->htr->commit(null, __METHOD__);
        } catch(AException $e) {
            $this->htr->rollback(__METHOD__);

            return false;
        }

        return true;
    }

    public function getLatestHashtagTrends() {
        try {
            $result = $this->htr->getLatestHashtagTrendsEntry();

            if($result === null) {
                throw new GeneralException('Could not find any hashtag trends.');
            }

            $result = unserialize($result['data']);

            return $result;
        } catch(AException $e) {
            return null;
        }
    }
}

?>