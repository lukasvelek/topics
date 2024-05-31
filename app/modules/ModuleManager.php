<?php

namespace App\Modules;

use Exception;

class ModuleManager {
    public function __construct() {

    }

    public function loadModules() {
        $modules = [];

        $folders = scandir(__DIR__);

        unset($folders[0], $folders[1]);

        foreach($folders as $folder) {
            $realPath = __DIR__ . '\\' . $folder;

            if(is_dir($realPath)) {
                $modules[] = $folder;
            }
        }

        return $modules;
    }

    public function createModule(string $name) {
        if(is_dir(__DIR__ . '\\' . $name) && is_file(__DIR__ . '\\' . $name . '\\' . $name . '.php')) {
            $className = '\\App\\Modules\\' . $name . '\\' . $name;

            return new $className();
        } else {
            throw new Exception('Module \'' . $name . '\' has not been implemented yet!');
        }
    }
}

?>