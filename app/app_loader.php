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
    global $cfg;

    $data = serialize(['files' => $files, 'created_on' => date('Y-m-d H:i:s')]);

    file_put_contents($cfg['CACHE_DIR'] . 'Container_' . md5(date('Y-m-d')) . '.tmp', $data);
}

/**
 * Returns a "container" -> a list of all files saved to a tmp file
 * 
 * @return array File array
 */
function getContainer() {
    global $cfg;

    if(file_exists($cfg['CACHE_DIR'] . 'Container_' . md5(date('Y-m-d')) . '.tmp')) {
        return unserialize(file_get_contents($cfg['CACHE_DIR'] . 'Container_' . md5(date('Y-m-d')) . '.tmp'))['files'];
    } else {
        return [];
    }
}

/**
 * Requires all given files
 * 
 * @param array $files File array
 */
function requireFiles(array $files, bool $createContainer) {
    $filesOrdered = [];
    $skipped = [];

    $__MAX__ = 1000;
    $x = 0;
    while(true) {
        if(empty($skipped)) {
            $files2 = $files;
        } else {
            $files2 = $skipped;
            $skipped = [];
        }

        foreach($files2 as $realPath => $file) {
            try {
                @require_once($realPath);
                $filesOrdered[$realPath] = $file;
            } catch(Error $e) {
                $skipped[$realPath] = $file;
            }
        }

        if(empty($skipped)) {
            break;
        }

        if($x >= $__MAX__) {
            break;
        }

        $x++;
    }

    if(!empty($skipped)) {
        throw new RuntimeException('Could not find these files: [' . implode(', ', $skipped) . '].', 9999);
    }

    if($createContainer) {
        createContainer($filesOrdered);
    }
}

/**
 * Principle of loading application files:
 * 
 * 1. The loader tries to obtain a "container" that contains a sorted list of paths to found necessary files
 * 2A. If the "container" does not exist, the loader search for all files (except for folders, files or file extensions defined)
 * 2A2. The loader sorts these files as: Interfacers, Abstract classes and Classes
 *          - the Classes are sorted accordingly to the extending classes
 *                  - e.g.: class A extends class B -> class B is loaded before class A
 * 
 * 2B. The container is found and the sorted files are loaded as an array
 * 3. Files are looped through and "required"
 * (4. If the container didn't exist in the first place, it is also created)
 */

$files = getContainer();

$createContainer = false;
if(empty($files)) {
    $createContainer = true;
    getFilesInFolderRecursively(__DIR__, $files, [], ['app_loader.php'], ['html', 'distrib', 'bak']);
    sortFilesByPriority($files);
}

requireFiles($files, $createContainer);

?>