<?php

namespace App\Exceptions;

use App\Core\Datetypes\DateTime;
use App\Core\FileManager;
use App\Core\HashManager;
use App\Modules\TemplateObject;
use Exception;
use Throwable;

abstract class AException extends Exception {
    protected function __construct(string $name, string $message, ?Throwable $previous = null, bool $createFile = true) {
        parent::__construct($message, 9999, $previous);

        if($createFile && FileManager::folderExists('logs\\')) {
            $this->createExceptionFile($name, $message);
        }
    }

    private function createExceptionFile(string $name, string $message) {
        global $app;

        if($app === null) {
            return;
        }

        $templateContent = FileManager::loadFile(__DIR__ . '\\templates\\common.html');
        $to = new TemplateObject($templateContent);

        $trace = $this->getTrace();
        $callstack = '';

        $i = 1;
        foreach($trace as $t) {
            $script = $t['file'];
            $line = $t['line'];
            $function = $t['function'];
            $args = $t['args'];
            $argString = '';

            if(!is_array($args) || (count($args) > 1 && is_object($args[0]))) {
                $argString = '[\'' . var_export($args, true) . '\']';
            } else {
                if(count($args) > 1) {
                    $tmp = [];
                    foreach($args as $arg) {
                        $tmp[] = var_export($arg, true);
                    }
                    $args = $tmp;
                    $argString = '[\'' . implode('\', \'', $args) . '\']';
                }
            }

            $line = '#' . $i . ' Script: \'' . $script . '\' on line ' . $line . ' - method: ' . $function . '() with args ' . $argString;

            $callstack .= $line . "<br>";

            $i++;
        }

        $to->name = $name;
        $to->message = $message;
        $to->callstack = $callstack;

        $to->render();
        $content = $to->getRenderedContent();

        $hash = HashManager::createHash(8, false);

        $date = new DateTime();
        $date->format('Y-m-d_H-i-s');

        $filePath = 'exception_' . $date . '_' . $hash . '.html';

        FileManager::saveFile($app->cfg['APP_REAL_DIR'] . $app->cfg['LOG_DIR'], $filePath, $content);
    }
}

?>