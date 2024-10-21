<?php

namespace App\Exceptions;

use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use App\Core\HashManager;
use App\Modules\TemplateObject;
use Exception;
use Throwable;

abstract class AException extends Exception {
    private string $hash;
    private string $html;

    protected function __construct(string $name, string $message, ?Throwable $previous = null, bool $createFile = true) {
        $this->hash = HashManager::createHash(8, false);
        
        parent::__construct($message /*. ' [' . $this->hash . ']'*/, 9999, $previous);

        $this->html = $this->createHTML($name, $message);

        if($createFile && FileManager::folderExists('logs\\')) {
            $this->createExceptionFile($name, $message);
        }
    }

    public function getHash() {
        return $this->hash;
    }

    private function createHTML(string $name, string $message) {
        $templateContent = FileManager::loadFile(__DIR__ . '\\templates\\common.html');
        $to = new TemplateObject($templateContent);

        $trace = $this->getTrace();
        $callstack = '';

        $i = 1;
        foreach($trace as $t) {
            $script = $t['file'];
            $line = $t['line'];
            $function = $t['function'];
            $args = $t['args'] ?? null;
            $argString = '';

            if(!is_array($args) || (count($args) > 1 && is_object($args[0]))) {
                $argString = '[\'' . var_export($args, true) . '\']';
            } else {
                if(count($args) > 1) {
                    $tmp = [];
                    foreach($args as $arg) {
                        $tmp[] = @var_export($arg, true);
                    }
                    $args = $tmp;
                    //$argString = '[\'' . implode('\', \'', $args) . '\']';
                }
            }

            $line = '#' . $i . ' Script: \'' . $script . '\' on line ' . $line . ' - method: ' . $function . '()';

            $callstack .= $line . "<br>";

            $i++;
        }

        $to->name = $name;
        $to->message = $message;
        $to->callstack = $callstack;

        $to->render();
        return $to->getRenderedContent();
    }

    public function getExceptionHTML() {
        return $this->html;
    }

    private function createExceptionFile(string $name, string $message) {
        global $app;

        if($app === null) {
            return;
        }

        $date = new DateTime();
        $date->format('Y-m-d_H-i-s');

        $filePath = 'exception_' . $date . '_' . $this->hash . '.html';

        FileManager::saveFile($app->cfg['APP_REAL_DIR'] . $app->cfg['LOG_DIR'], $filePath, $this->html);
    }
}

?>