<?php

/**
 * Creates a list of all files in the given folder
 * 
 * @param string $folder Folder to look in
 * @param array $files Link to array of found files
 * @param array $skipFolders List of parent folders to skip
 * @param array $skipFiles List of files to skip
 * @param array $skipExtensions List of extensions to skip
 */
function getFilesInFolderRecursively(string $folder, array &$files, array $skipFolders = [], array $skipFiles = [], array $skipExtensions = []) {
    $objects = scandir($folder);

    unset($objects[0], $objects[1]);

    foreach($objects as $object) {
        $realObject = $folder . '\\' . $object;

        if(!is_dir($realObject)) {
            if(in_array($object, $skipFiles)) {
                continue;
            }

            if(str_contains($object, '.')) {
                $filenameParts = explode('.', $object);
                $extension = $filenameParts[count($filenameParts) - 1];

                if(in_array($extension, $skipExtensions)) {
                    continue;
                } else {
                    $files[$realObject] = $object;
                }
            }
        } else {
            if(!in_array($object . '\\', $skipFolders)) {
                getFilesInFolderRecursively($folder . '\\' . $object, $files, $skipFolders, $skipFiles, $skipExtensions);
            }
        }
    }
}

/**
 * Sorts files by priority:
 * 1. Interfaces
 * 2. Abstract classes
 * 3. Classes
 * 
 * @param array $files Link to array with files
 */
function sortFilesByPriority(array &$files) {
    $interfaces = [];
    $abstractClasses = [];
    $classes = [];

    foreach($files as $realPath => $file) {
        if(str_starts_with($file, 'I')) {
            if(ctype_upper($file[1])) {
                $interfaces[$realPath] = $file;
            } else {
                $classes[$realPath] = $file;
            }
        } else if(str_starts_with($file, 'A')) {
            if(ctype_upper($file[1])) {
                $abstractClasses[$realPath] = $file;
            } else {
                $classes[$realPath] = $file;
            }
        } else {
            $classes[$realPath] = $file;
        }
    }

    $files = array_merge($interfaces, $abstractClasses, $classes);
}

/**
 * Creates a "container" -> a list of all files saved to a tmp file
 */
function createContainer($files) {
    $data = serialize(['files' => $files, 'created_on' => date('Y-m-d H:i:s')]);

    file_put_contents('cache\\Container_' . md5(date('Y-m-d')) . '.tmp', $data);
}

/**
 * Returns a "container" -> a list of all files saved to a tmp file
 * 
 * @return array File array
 */
function getContainer() {
    if(file_exists('cache\\Container_' . md5(date('Y-m-d')) . '.tmp')) {
        return unserialize(file_get_contents('cache\\Container_' . md5(date('Y-m-d')) . '.tmp'))['files'];
    } else {
        return [];
    }
}

/**
 * Requires all given files
 * 
 * @param array $files File array
 */
function requireFiles(array $files) {
    foreach($files as $realPath => $file) {
        require_once($realPath);
    }
}

$files = getContainer();

if(empty($files)) {
    getFilesInFolderRecursively(__DIR__, $files, [], ['app_loader.php'], ['html', 'distrib', 'bak']);
    sortFilesByPriority($files);
    createContainer($files);
}

requireFiles($files);

?>