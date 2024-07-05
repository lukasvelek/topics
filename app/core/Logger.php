<?php

namespace App\Logger;

use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use Exception;
use QueryBuilder\ILoggerCallable;

class Logger implements ILoggerCallable {
    public const LOG_INFO = 'info';
    public const LOG_WARNING = 'warning';
    public const LOG_ERROR = 'error';
    public const LOG_SQL = 'sql';
    public const LOG_STOPWATCH = 'stopwatch';
    public const LOG_EXCEPTION = 'exception';
    public const LOG_CACHE = 'cache';

    private int $logLevel;
    private int $sqlLogLevel;
    private ?string $specialFilename;
    private array $cfg;
    private int $stopwatchLogLevel;
    private bool $separateSQLLogging;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->sqlLogLevel = $this->cfg['SQL_LOG_LEVEL'];
        $this->logLevel = $this->cfg['LOG_LEVEL'];
        $this->specialFilename = null;
        $this->stopwatchLogLevel = $this->cfg['LOG_STOPWATCH'];
        $this->separateSQLLogging = $this->cfg['SQL_SEPARATE_LOGGING'];
    }

    public function getCfg() {
        return $this->cfg;
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
        $oldSpecialFilename = $this->specialFilename;
        $this->specialFilename = 'service_log';

        $date = new DateTime();
        $text = '[' . $date . '] [' . strtoupper($type) . '] ' . $serviceName . ': ' . $text;

        $this->writeLog($text);

        $this->specialFilename = $oldSpecialFilename;
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

    public function exception(Exception $e, string $method) {
        $text = 'Exception: ' . $e->getMessage() . '. Call stack: ' . $e->getTraceAsString();

        $this->log($method, $text, self::LOG_EXCEPTION);
    }

    public function sql(string $text, string $method, ?float $msTaken) {
        $this->logSQL($method, $text, ($msTaken ?? 0.0));
    }

    public function logSQL(string $method, string $text, float $msTaken) {
        $date = new DateTime();
        $newText = '[' . $date . '] [' . strtoupper(self::LOG_SQL) . '] [' . (int)($msTaken) . ' ms] ' . $method . '(): ' . $text;

        if($this->separateSQLLogging && $this->sqlLogLevel >= 1) {
            $newText = '[' . $date . '] [' . strtoupper(self::LOG_SQL) . '] [' . $msTaken . ' ms] ' . $text;

            $oldSpecialFilename = $this->specialFilename;
            $this->specialFilename = 'sql_log';
            $this->writeLog($newText);
            $this->specialFilename = $oldSpecialFilename;
        } else {
            if($this->sqlLogLevel >= 1) {
                $this->writeLog($newText);
            }
        }
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

            case self::LOG_CACHE:
                if($this->logLevel >= 4) {
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
        }
    }

    public function setFilename(?string $filename) {
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

    public function logCache(string $method, bool $hit) {
        $text = 'Cache ' . ($hit ? 'hit' : 'miss') . '.';

        $this->log($method, $text, self::LOG_CACHE);
    }
}

?>