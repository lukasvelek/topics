<?php

namespace App\Exceptions;

use App\Configuration;
use App\Core\FileManager;
use App\Core\HashManager;
use App\Modules\TemplateObject;
use Exception;
use Throwable;

abstract class AException extends Exception {
    protected function __construct(string $name, string $message, ?Throwable $previous = null) {
        parent::__construct($message, 9999, $previous);

        if(FileManager::folderExists('logs\\')) {
            $this->createExceptionFile($name, $message);
        }
    }

    private function createExceptionFile(string $name, string $message) {
        global $app;

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
            $argString = '[]';

            if(!is_array($args) || (count($args) > 1 && is_object($args[0]))) {
                $argString = '[\'' . var_export($args, true) . '\']';
            } else {
                if(count($args) > 1) {
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

        $filePath = 'exception_' . date('Y-m-d_H-i-s') . '_' . $hash . '.html';

        FileManager::saveFile($app->cfg['APP_REAL_DIR'] . $app->cfg['LOG_DIR'], $filePath, $content);
    }
}

?>