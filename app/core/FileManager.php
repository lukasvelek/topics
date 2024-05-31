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
}

?>