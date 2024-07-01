<?php

namespace App\Logger;

use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use QueryBuilder\ILoggerCallable;

class Logger implements ILoggerCallable {
    public const LOG_INFO = 'info';
    public const LOG_WARNING = 'warning';
    public const LOG_ERROR = 'error';
    public const LOG_SQL = 'sql';
    public const LOG_STOPWATCH = 'stopwatch';

    private int $logLevel;
    private int $sqlLogLevel;
    private ?string $specialFilename;
    private array $cfg;
    private int $stopwatchLogLevel;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->sqlLogLevel = $this->cfg['SQL_LOG_LEVEL'];
        $this->logLevel = $this->cfg['LOG_LEVEL'];
        $this->specialFilename = null;
        $this->stopwatchLogLevel = $this->cfg['LOG_STOPWATCH'];
    }

    public function stopwatch(callable $function, string $method) {
        $time = time();

        $result = $function();

        $diff = time() - $time;

        $diff = $diff / 1000;

        $this->log($method, 'Time taken: ' . $diff . ' seconds', self::LOG_STOPWATCH);

        return $result;
    }

    public function serviceInfo(string $text, string $serviceName) {
        $this->logService($serviceName, $text, self::LOG_INFO);
    }

    public function serviceError(string $text, string $serviceName) {
        $this->logService($serviceName, $text, self::LOG_ERROR);
    }

    public function logService(string $serviceName, string $text, string $type = self::LOG_INFO) {
        $this->specialFilename = 'service_log';

        $date = new DateTime();
        $text = '[' . $date . '] [' . strtoupper($type) . '] ' . $serviceName . ': ' . $text;

        $this->writeLog($text);

        $this->specialFilename = null;
    }

    public function info(string $text, string $method) {
        $this->log($method, $text);
    }

    public function warning(string $text, string $method) {
        $this->log($method, $text, self::LOG_WARNING);
    }

    public function error(string $text, string $method) {
        $this->log($method, $text, self::LOG_ERROR);
    }

    public function sql(string $text, string $method) {
        $this->log($method, $text, self::LOG_SQL);
    }

    public function log(string $method, string $text, string $type = self::LOG_INFO) {
        $date = new DateTime();
        $text = '[' . $date . '] [' . strtoupper($type) . '] ' . $method . '(): ' . $text;

        switch($type) {
            case self::LOG_STOPWATCH:
                if($this->stopwatchLogLevel >= 1) {
                    $this->writeLog($text);
                }
                break;

            case self::LOG_INFO:
                if($this->logLevel >= 3) {
                    $this->writeLog($text);
                }
                break;

            case self::LOG_WARNING:
                if($this->logLevel >= 2) {
                    $this->writeLog($text);
                }
                break;
            
            case self::LOG_ERROR:
                if($this->logLevel >= 1) {
                    $this->writeLog($text);
                }
                break;

            case self::LOG_SQL:
                if($this->sqlLogLevel >= 1) {
                    $this->writeLog($text);
                }
                break;
        }
    }

    public function setFilename(string $filename) {
        $this->specialFilename = $filename;
    }

    private function writeLog(string $text) {
        $folder = $this->cfg['APP_REAL_DIR'] . $this->cfg['LOG_DIR'];

        $date = new DateTime();
        $date->format('Y-m-d');
        
        if($this->specialFilename !== null) {
            $file = $this->specialFilename . '_' . $date . '.log';
        } else {
            $file = 'log_' . $date . '.log';
        }

        if(!FileManager::folderExists($folder)) {
            FileManager::createFolder($folder);
        }

        FileManager::saveFile($folder, $file, $text . "\r\n");
    }
}

?>