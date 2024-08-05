<?php

namespace App\Services;

use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Exceptions\GeneralException;
use App\Logger\Logger;
use App\Repositories\UserRegistrationRepository;
use Exception;

class OldRegistrationConfirmationLinkRemovingService extends AService {
    private const BATCH_LIMIT = 20;

    private UserRegistrationRepository $urr;

    public function __construct(Logger $logger, ServiceManager $serviceManager, UserRegistrationRepository $urr) {
        parent::__construct('OldRegistrationConfirmationLinkRemoving', $logger, $serviceManager);

        $this->urr = $urr;
    }

    public function run() {
        try {
            $this->serviceStart();

            $this->innerRun();

            $this->serviceStop();
        } catch(AException|Exception $e) {
            try {
                $this->serviceStop();
            } catch(AException|Exception $e2) {}

            $this->logError($e->getMessage());
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $run = true;

        $offset = 0;
        while($run) {
            $this->logInfo('Loading batch #' . (($offset / self::BATCH_LIMIT) + 1) . '.');

            $ids = $this->urr->getInactiveOrExpiredRegistrations(self::BATCH_LIMIT, $offset);

            if(empty($ids)) {
                $this->logInfo('No inactive or expired registration confirmations found.');
                $run = false;
                break;
            } else {
                $this->logInfo('Found ' . count($ids) . ' registration confirmations to delete.');
            }

            foreach($ids as $id) {
                try {
                    $this->logInfo('Deleting entry #' . $id . '.');
                    if(!$this->urr->deleteRegistration($id)) {
                        throw new GeneralException('Registration confirmation link could not be deleted.');
                    }
                } catch(AException $e) {
                    $this->logError('Could not delete entry #' . $id . '. Reason: ' . $e->getMessage());
                }
            }

            $offset += self::BATCH_LIMIT;
        }
    }
}

?>