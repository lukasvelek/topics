<?php

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

function requireFiles(array $files) {
    foreach($files as $realPath => $file) {
        require_once($realPath);
    }
}

$files = [];

getFilesInFolderRecursively(__DIR__, $files, ['ajax\\'], ['app_loader.php'], ['html']);
sortFilesByPriority($files);
requireFiles($files);

?>