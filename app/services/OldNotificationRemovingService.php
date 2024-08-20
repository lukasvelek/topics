<?php

namespace App\Services;

use App\Core\Datetypes\DateTime;
use App\Core\ServiceManager;
use App\Exceptions\AException;
use App\Logger\Logger;
use App\Managers\NotificationManager;
use Exception;

class OldNotificationRemovingService extends AService {
    private const DATE_BEFORE = '-1d';
    private const STEP = 100;

    private NotificationManager $nm;

    public function __construct(Logger $logger, ServiceManager $serviceManager, NotificationManager $nm) {
        parent::__construct('OldNotificationRemoving', $logger, $serviceManager);

        $this->nm = $nm;
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
        $notificationCount = $this->getTotalNotificationCount();

        $date = new DateTime();
        $date->modify(self::DATE_BEFORE);
        $date = $date->getResult();

        $this->logInfo(sprintf('Found %d seen notifications that are older than %s.', $notificationCount, $date));
        
        $offset = 0;
        while($ids = $this->getOldNotificationIds($offset)) {
            $this->logInfo(sprintf('Starting batch #%d with %d notifications.', ($offset + 1), count($ids)));
            $this->deleteNotifications($ids);

            $offset++;
        }
    }

    private function getTotalNotificationCount() {
        $date = new DateTime();
        $date->modify(self::DATE_BEFORE);
        $date = $date->getResult();

        return count($this->nm->getOldSeenNotifications($date));
    }

    private function getOldNotificationIds(int $offset) {
        $date = new DateTime();
        $date->modify(self::DATE_BEFORE);
        $date = $date->getResult();

        return $this->nm->getOldSeenNotificationsStep($date, self::STEP, (self::STEP * $offset));
    }

    private function deleteNotifications(array $ids) {
        $this->nm->bulkRemoveNotifications($ids);

        $this->logInfo('Removed ' . count($ids) . ' notifications.');
    }
}

?>