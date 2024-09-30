<?php

namespace App\Core;

use App\Constants\SystemServiceStatus;
use App\Exceptions\AException;
use App\Exceptions\ServiceException;
use App\Repositories\SystemServicesRepository;
use Exception;

/**
 * Service manager allows running background services
 * 
 * @author Lukas Velek
 */
class ServiceManager {
    private array $cfg;
    private SystemServicesRepository $ssr;

    /**
     * Class constructor
     * 
     * @param array $cfg Application configuration
     * @param SystemServicesRepository $ssr SystemServicesRepository instance
     */
    public function __construct(array $cfg, SystemServicesRepository $ssr) {
        $this->cfg = $cfg;
        $this->ssr = $ssr;
    }

    /**
     * Starts a background PHP CLI and runs the given script
     * 
     * @param string $scriptPath Script path to be run in background
     * @return bool True if the script was run successfully or false if not
     */
    public function runService(string $scriptPath) {
        $phpExe = $this->cfg['PHP_DIR_FULLPATH'] . 'php.exe';

        $serviceFile = $this->cfg['APP_REAL_DIR'] . 'services\\' . $scriptPath;

        $cmd = $phpExe . ' ' . $serviceFile;

        if(substr(php_uname(), 0, 7) == 'Windows') {
            $p = popen("start /B " . $cmd, "w");
            if($p === false) {
                return false;
            }
            $status = pclose($p);
            if($status == -1) {
                return false;
            }
        } else {
            $status = exec($cmd . " > /dev/null &");
            if($status === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates service status to "Running"
     * 
     * @param string $serviceTitle Service name
     */
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

    /**
     * Updates service status to "Not running"
     * 
     * @param string $serviceTitle Service name
     */
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

    /**
     * Returns service ID
     * 
     * @param string $serviceTitle Service name
     * @return string Service ID
     */
    private function getServiceId(string $serviceTitle) {
        $service = $this->ssr->getServiceByTitle($serviceTitle);

        if($service === null) {
            throw new ServiceException('Could not retrieve service information from the database.');
        }

        return $service->getId();
    }
}

?>