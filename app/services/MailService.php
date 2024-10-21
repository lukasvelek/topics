<?php

namespace App\Services;

use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\MailManager;
use Exception;

class MailService extends AService {
    private const MAIL_BATCH_LIMIT = 20;

    private MailManager $mailManager;
    
    public function __construct(Logger $logger, ServiceManager $serviceManager, MailManager $mailManager) {
        parent::__construct('Mail', $logger, $serviceManager);

        $this->mailManager = $mailManager;
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
            
            throw $e;
        }
    }

    private function innerRun() {
        // Service executes all commands here
        $run = true;

        $offset = 0;
        while($run) {
            $this->logInfo('Loading email batch #' . (($offset / self::MAIL_BATCH_LIMIT) + 1) . '.');

            $entries = $this->mailManager->getAllUnsentEmails(self::MAIL_BATCH_LIMIT, $offset);

            if(empty($entries)) {
                $this->logInfo('No emails found in queue.');
                $run = false;
                break;
            } else {
                $this->logInfo('Found ' . count($entries) . ' emails in queue.');
            }

            $delete = [];
            foreach($entries as $entry) {
                try {
                    $this->logInfo('Sending email with entry #' . $entry->getId() . '.');
                    $this->mailManager->sendEmail($entry);
                    $delete[] = $entry->getId();
                } catch(AException $e) {
                    $this->logError('Could not send email. Reason: ' . $e->getMessage());
                }
            }
            
            if(!empty($delete)) {
                foreach($delete as $id) {
                    try {
                        $this->mailManager->mailRepository->beginTransaction();
                        $this->mailManager->deleteEmailEntry($id);
                        $this->mailManager->mailRepository->commit(null, __METHOD__);
                    } catch(AException|Exception $e) {
                        $this->mailManager->mailRepository->rollback();
                        $this->logError('Could not delete entry #' . $id . '. Reason: ' . $e->getMessage());
                    }
                }
            }

            $offset += self::MAIL_BATCH_LIMIT;
        }
    }
}

?>