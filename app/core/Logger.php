<?php

namespace App\Logger;

use App\Configuration;
use App\Core\FileManager;
use QueryBuilder\ILoggerCallable;

class Logger implements ILoggerCallable {
    public const LOG_INFO = 'info';
    public const LOG_WARNING = 'warning';
    public const LOG_ERROR = 'error';
    public const LOG_SQL = 'sql';

    private int $logLevel;
    private int $sqlLogLevel;
    private ?string $specialFilename;
    private array $cfg;

    public function __construct(array $cfg) {
        $this->cfg = $cfg;

        $this->sqlLogLevel = $this->cfg['SQL_LOG_LEVEL'];
        $this->logLevel = $this->cfg['LOG_LEVEL'];
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

    public function setFilename(string $filename) {
        $this->specialFilename = $filename;
    }

    private function writeLog(string $text) {
        $folder = $this->cfg['APP_REAL_DIR'] . $this->cfg['LOG_DIR'];
        
        if($this->specialFilename !== null) {
            $file = $this->specialFilename . '_' . date('Y-m-d') . '.log';
        } else {
            $file = 'log_' . date('Y-m-d') . '.log';
        }

        if(!FileManager::folderExists($folder)) {
            FileManager::createFolder($folder);
        }

        FileManager::saveFile($folder, $file, $text . "\r\n");
    }
}

?>