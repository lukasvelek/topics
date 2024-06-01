<?php

namespace App\Logger;

use App\Configuration;
use App\Core\FileManager;

class Logger {
    public const LOG_INFO = 'info';
    public const LOG_WARNING = 'warning';
    public const LOG_ERROR = 'error';
    public const LOG_SQL = 'sql';

    private int $logLevel;
    private int $sqlLogLevel;

    public function __construct() {
        $this->sqlLogLevel = Configuration::getLogLevelSQL();
        $this->logLevel = Configuration::getLogLevel();
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
        $text = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($type) . '] ' . $method . '(): ' . $text;

        switch($type) {
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

    private function writeLog(string $text) {
        $file = Configuration::getAppRealDir() . Configuration::getLogDir() . Configuration::getAppName() . '_log_' . date('Y-m-d') . '.log';

        FileManager::saveFile($file, $text);
    }
}

?>