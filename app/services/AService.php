<?php

namespace App\Services;

use App\Core\ServiceManager;
use App\Logger\Logger;

abstract class AService implements IRunnable {
    protected Logger $logger;
    protected ServiceManager $serviceManager;
    protected string $serviceName;

    protected function __construct(string $serviceName, Logger $logger, ServiceManager $serviceManager) {
        $this->serviceName = $serviceName;
        $this->logger = $logger;
        $this->serviceManager = $serviceManager;
    }

    protected function serviceStart() {
        $this->serviceManager->startService($this->serviceName);
        $this->logInfo('Service ' . $this->serviceName . ' started.');
    }

    protected function serviceStop() {
        $this->serviceManager->stopService($this->serviceName);
        $this->logInfo('Service ' . $this->serviceName . ' ended.');
    }

    public function logInfo(string $text) {
        $this->logger->serviceInfo($text, $this->serviceName);
    }

    public function logError(string $text) {
        $this->logger->serviceError($text, $this->serviceName);
    }
}

?>