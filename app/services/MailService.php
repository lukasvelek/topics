<?php

namespace App\Services;

use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\MailManager;
use Exception;

class MailService extends AService {
    private const MAIL_BATCH_LIMIT = 20;
    private const STOP_ON_SEND_EXCEPTION = false;

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
            $this->logError($e->getMessage());
        }
    }

    private function innerRun() {
        // Service executes all commands here

        $run = true;

        $offset = 0;
        while($run) {
            $this->logInfo('Loading email batch #' . (($offset + 1) / self::MAIL_BATCH_LIMIT) . '.');

            $entries = $this->mailManager->getAllUnsentEmails(self::MAIL_BATCH_LIMIT, $offset);

            if(empty($entries)) {
                $this->logInfo('No emails found in queue.');
                $run = false;
                break;
            } else {
                $this->logInfo('Found ' . count($entries) . ' emails in queue.');
            }

            foreach($entries as $entry) {
                try {
                    $this->logInfo('Sending email with entry #' . $entry->getId() . '.');
                    $this->mailManager->sendEmail($entry);
                } catch(AException $e) {
                    $this->logError('Could not send email. Reason: ' . $e->getMessage());

                    if(self::STOP_ON_SEND_EXCEPTION) {
                        $run = false;
                        break 2;
                    }
                }
            }

            $offset += self::MAIL_BATCH_LIMIT;
        }
    }
}

?>