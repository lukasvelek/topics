<?php

namespace App\Core;

use App\Constants\SystemServiceStatus;
use App\Repositories\SystemServicesRepository;

class ServiceManager {
    private array $cfg;
    private SystemServicesRepository $ssr;

    public function __construct(array $cfg, SystemServicesRepository $ssr) {
        $this->cfg = $cfg;
        $this->ssr = $ssr;
    }

    public function runService(string $scriptPath) {
        $phpExe = $this->cfg['PHP_DIR_FULLPATH'] . 'php.exe';

        $serviceFile = $this->cfg['APP_REAL_DIR'] . 'services\\' . $scriptPath;

        $cmd = $phpExe . ' ' . $serviceFile;

        if(substr(php_uname(), 0, 7) == 'Windows') {
            pclose(popen("start /B " . $cmd, "w"));
        } else {
            exec($cmd . " > /dev/null &");
        }

        return true;
    }

    public function startService(string $serviceTitle) {
        $this->ssr->updateService($this->getServiceId($serviceTitle), ['dateStarted' => date('Y-m-d H:i:s'), 'dateEnded' => null, 'status' => SystemServiceStatus::RUNNING]);
    }

    public function stopService(string $serviceTitle) {
        $this->ssr->updateService($this->getServiceId($serviceTitle), ['dateEnded' => date('Y-m-d H:i:s'), 'status' => SystemServiceStatus::NOT_RUNNING]);
    }

    private function getServiceId(string $serviceTitle) {
        $service = $this->ssr->getServiceByTitle($serviceTitle);

        return $service->getId();
    }
}

?>