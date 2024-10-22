<?php

namespace App\Logger;

use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use Exception;
use QueryBuilder\ILoggerCallable;

/**
 * Logger allows logging information, warnings, errors
 * 
 * @author Lukas Velek
 */
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

    /**
     * Class constructor
     * 
     * @param array $cfg Application configuration
     */
    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->sqlLogLevel = $this->cfg['SQL_LOG_LEVEL'];
        $this->logLevel = $this->cfg['LOG_LEVEL'];
        $this->specialFilename = null;
        $this->stopwatchLogLevel = $this->cfg['LOG_STOPWATCH'];
        $this->separateSQLLogging = $this->cfg['SQL_SEPARATE_LOGGING'];
    }

    /**
     * Returns application configuration
     * 
     * @return array Application configuration
     */
    public function getCfg() {
        return $this->cfg;
    }

    /**
     * Measures the time taken to process given function and returns the result of the given function
     * 
     * @param callback $function Function to measure
     * @param string $method Calling method name
     * @return mixed Result of $function
     */
    public function stopwatch(callable $function, string $method) {
        $time = time();

        $result = $function();

        $diff = time() - $time;

        $this->log($method, 'Time taken: ' . $diff . ' seconds', self::LOG_STOPWATCH);

        return $result;
    }

    /**
     * Logs information - for services only
     * 
     * @param string $text Text
     * @param string $serviceName Service name
     */
    public function serviceInfo(string $text, string $serviceName) {
        $this->logService($serviceName, $text, self::LOG_INFO);
    }

    /**
     * Logs error - for services only
     * 
     * @param string $text Text
     * @param string $serviceName Service name
     */
    public function serviceError(string $text, string $serviceName) {
        $this->logService($serviceName, $text, self::LOG_ERROR);
    }

    /**
     * Saves service message to the service log file
     * 
     * @param string $serviceName Service name
     * @param string $text Text
     * @param string $type Message type
     */
    private function logService(string $serviceName, string $text, string $type = self::LOG_INFO) {
        $oldSpecialFilename = $this->specialFilename;
        $this->specialFilename = 'service_log';

        $date = new DateTime();
        $text = '[' . $date . '] [' . strtoupper($type) . '] ' . $serviceName . ': ' . $text;

        $this->writeLog($text);

        $this->specialFilename = $oldSpecialFilename;
    }

    /**
     * Logs SQL query
     * 
     * @param string $sqlQuery SQL string
     * @param string $method Calling method
     * @param null|float $msTaken Milliseconds taken
     */
    public function sql(string $sql, string $method, ?float $msTaken) {
        $this->logSQL($method, $sql, ($msTaken ?? 0.0));
    }

    /**
     * Saves SQL query log to the SQL log file
     * 
     * @param string $method Calling method
     * @param string $sql SQL string
     * @param float $msTaken Milliseconds taken
     */
    private function logSQL(string $method, string $sql, float $msTaken) {
        $date = new DateTime();
        $newText = '[' . $date . '] [' . strtoupper(self::LOG_SQL) . '] [' . (int)($msTaken) . ' s] ' . $method . '(): ' . $sql;

        if($this->separateSQLLogging && $this->sqlLogLevel >= 1) {
            $newText = '[' . $date . '] [' . strtoupper(self::LOG_SQL) . '] [' . $msTaken . ' s] ' . $method . '(): ' . $sql;

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

    /**
     * Logs information
     * 
     * @param string $text Text
     * @param string $method Calling method
     */
    public function info(string $text, string $method) {
        $this->log($method, $text);
    }

    /**
     * Logs warning
     * 
     * @param string $text Text
     * @param string $method Calling method
     */
    public function warning(string $text, string $method) {
        $this->log($method, $text, self::LOG_WARNING);
    }

    /**
     * Logs error
     * 
     * @param string $text Text
     * @param string $method Calling method
     */
    public function error(string $text, string $method) {
        $this->log($method, $text, self::LOG_ERROR);
    }

    /**
     * Logs exception
     * 
     * @param Exception $e Exception instance
     * @param string $method Calling method
     */
    public function exception(Exception $e, string $method) {
        $text = 'Exception: ' . $e->getMessage() . '. Call stack: ' . $e->getTraceAsString();

        $this->log($method, $text, self::LOG_EXCEPTION);
    }

    /**
     * Saves message to the log file
     * 
     * @param string $method Calling method
     * @param string $text Text
     * @param string $type Message type
     * @return bool True on success or false on failure
     */
    protected function log(string $method, string $text, string $type = self::LOG_INFO) {
        $date = new DateTime();
        $text = '[' . $date . '] [' . strtoupper($type) . '] ' . $method . '(): ' . $text;

        switch($type) {
            case self::LOG_STOPWATCH:
                if($this->stopwatchLogLevel >= 1) {
                    $result = $this->writeLog($text);
                }
                break;

            case self::LOG_CACHE:
                if($this->logLevel >= 4 && $this->cfg['LOG_CACHE'] == 1) {
                    $result = $this->writeLog($text);
                }
                break;

            case self::LOG_INFO:
                if($this->logLevel >= 3) {
                    $result = $this->writeLog($text);
                }
                break;

            case self::LOG_WARNING:
                if($this->logLevel >= 2) {
                    $result = $this->writeLog($text);
                }
                break;
            
            case self::LOG_ERROR:
                if($this->logLevel >= 1) {
                    $result = $this->writeLog($text);
                }
                break;

            default:
                $result = false;
                break;
        }

        return $result;
    }

    /**
     * Sets custom filename. If set to null then no custom filename is set.
     * 
     * @param null|string $filename Custom filename
     */
    public function setFilename(?string $filename) {
        $this->specialFilename = $filename;
    }

    /**
     * Saves log message to the file
     * 
     * @param string $text Log message
     * @return bool True on success or false on failure
     */
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

        $result = FileManager::saveFile($folder, $file, $text . "\r\n", false, true);

        if($result !== false) {
            return true;
        } else {
            return false;
        }
    }
}

?>