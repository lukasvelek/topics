<?php

namespace App\Core;

use App\Exceptions\FileDoesNotExistException;

class FileManager {
    public static function fileExists(string $filePath) {
        return file_exists($filePath);
    }

    public static function loadFile(string $filePath) {
        if(!self::fileExists($filePath)) {
            throw new FileDoesNotExistException($filePath);
        }

        return file_get_contents($filePath);
    }

    public static function folderExists(string $dirPath) {
        return is_dir($dirPath);
    }

    public static function getFilesInFolder(string $dirPath) {
        $recursive = function (string $dirPath, array &$objects) {
            $contents = scandir($dirPath);

            unset($contents[0], $contents[1]);

            foreach($contents as $content) {
                $realObject = $dirPath . '\\' . $content;

                if(!is_dir($realObject)) {
                    $objects[$content] = $realObject;
                } else {
                    $this($realObject, $objects);
                }
            }
        };

        $objects = [];

        $recursive($dirPath, $objects);

        return $objects;
    }
}

?>