<?php

namespace App\Core;

use App\Constants\SystemServiceStatus;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use App\Repositories\SystemServicesRepository;
use Exception;

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
            $status = pclose(popen("start /B " . $cmd, "w"));
        } else {
            exec($cmd . " > /dev/null &");
        }

        return true;
    }

    public function startService(string $serviceTitle) {
        try {
            $serviceId = $this->getServiceId($serviceTitle);
        } catch(AException|Exception $e) {
            throw $e;
        }

        if(!$this->ssr->updateService($serviceId, ['dateStarted' => date('Y-m-d H:i:s'), 'dateEnded' => NULL, 'status' => SystemServiceStatus::RUNNING])) {
            throw new ServiceException('Could not update service status.');
        }
    }

    public function stopService(string $serviceTitle) {
        try {
            $serviceId = $this->getServiceId($serviceTitle);
        } catch(AException|Exception $e) {
            throw $e;
        }

        if(!$this->ssr->updateService($serviceId, ['dateEnded' => date('Y-m-d H:i:s'), 'status' => SystemServiceStatus::NOT_RUNNING])) {
            throw new ServiceException('Could not update service status.');
        }
    }

    private function getServiceId(string $serviceTitle) {
        $service = $this->ssr->getServiceByTitle($serviceTitle);

        if($service === null) {
            throw new ServiceException('Could not retrieve service information from the database.');
        }

        return $service->getId();
    }
}

?>