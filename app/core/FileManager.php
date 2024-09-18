<?php

namespace App\Core;

use App\Exceptions\FileDoesNotExistException;

/**
 * FileManager allows manipulating with files.
 * 
 * @author Lukas Velek
 */
class FileManager {
    private FileLockManager $flm;

    private function __construct() {
        $this->flm = new FileLockManager();
    }

    /**
     * Checks if file exists
     * 
     * @param string $filePath Path to the file
     * @return bool True if file exists or false if not
     */
    public static function fileExists(string $filePath) {
        return file_exists($filePath);
    }

    /**
     * Loads file content
     * 
     * @param string $filePath Path to the file
     * @return string|false File content or false if error occurred
     * @throws FileDoesNotExistException
     */
    public static function loadFile(string $filePath) {
        if(!self::fileExists($filePath)) {
            throw new FileDoesNotExistException($filePath);
        }

        $obj = new self();

        $locked = $obj->flm->lock($filePath);

        if($locked) {
            $handle = $obj->flm->getHandle($filePath);
        
            if($handle === null) {
                return false;
            }

            $content = fread($handle, filesize($filePath));

            $obj->flm->unlock($filePath);
        } else {
            $content = file_get_contents($filePath);
        }

        return $content;
    }

    /**
     * Checks if folder exists
     * 
     * @param string $dirPath Path to the directory
     * @return bool True if directory exists or false if not
     */
    public static function folderExists(string $dirPath) {
        return is_dir($dirPath);
    }

    /**
     * Returns all files in a given directory
     * 
     * @param string $dirPath Path to the directory (root in method's context)
     * @return array Array of filenames (key is relative path and value is absolute path)
     */
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

    /**
     * Saves file
     * 
     * @param string $path Path to the directory where the file will be saved
     * @param string $filename Filename
     * @param string|array $fileContent File content
     * @param bool $overwrite True if file content should be overwritten or false if not
     * @param bool $appendNewLine Only applicable if $fileContent is array. True if each element in $fileContent should be on a new line, or false if not
     * @return int|false Number of bytes written or false if error occurred
     */
    public static function saveFile(string $path, string $filename, string|array $fileContent, bool $overwrite = false, bool $appendNewLine = true) {
        if(is_array($fileContent)) {
            if($appendNewLine) {
                $fileContent = implode("\r\n", $fileContent);
            } else {
                $fileContent = implode('', $fileContent);
            }
        }

        if(!self::folderExists($path)) {
            self::createFolder($path);
        }

        if($overwrite === false) {
            return file_put_contents($path . $filename, $fileContent, FILE_APPEND);
        } else {
            return file_put_contents($path . $filename, $fileContent);
        }
    }

    /**
     * Creates a directory
     * 
     * @param string $dirPath Path to the directory
     * @param bool $recursive True if directory should be created recursively or false if not
     * @return bool True on success or false on failure
     */
    public static function createFolder(string $dirPath, bool $recursive = false) {
        return mkdir($dirPath, 0777, $recursive);
    }

    /**
     * Deletes a folder recursively
     * 
     * @param string $dirPath Path to the directory that should be deleted
     * @return bool True on success or false on failure
     */
    public static function deleteFolderRecursively(string $dirPath) {
        if(is_dir($dirPath)) {
            $objects = scandir($dirPath);
            
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dirPath . DIRECTORY_SEPARATOR . $object) && !is_link($dirPath . "/" . $object)) {
                        self::deleteFolderRecursively($dirPath. DIRECTORY_SEPARATOR .$object);
                    } else {
                        self::deleteFile($dirPath . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }

            return rmdir($dirPath);
        }

        return false;
    }

    /**
     * Deletes a file
     * 
     * @param string $filePath Path to the file
     * @return bool True on success or false on failure
     */
    public static function deleteFile(string $filePath) {
        if(self::fileExists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }
}

?>