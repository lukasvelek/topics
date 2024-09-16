<?php

namespace App\Core;

use App\Core\Datetypes\DateTime;

/**
 * Manages file locks
 * 
 * @author Lukas Velek
 */
class FileLockManager {
    private array $locks;

    /**
     * Class constructor
     */
    public function __construct() {
        $this->locks = [];
    }

    /**
     * Locks a given file
     * 
     * @param string $filePath Path to the file that should be locked
     * @return bool True on success or false on failure
     */
    public function lock(string $filePath) {
        $handle = @fopen($filePath, 'c+b');

        if($handle === false) {
            return false;
        }

        $this->locks[$filePath] = $handle;

        return flock($handle, LOCK_EX);
    }

    /**
     * Unlocks a given file
     * 
     * @param string $filePath Path to the file that should be unlocked
     * @return bool True on success or false on failure
     */
    public function unlock(string $filePath) {
        if(array_key_exists($filePath, $this->locks)) {
            unset($this->locks[$filePath]);
            return true;
        }

        return false;
    }

    /**
     * Returns file handle
     * 
     * @param string $filePath Path to the file
     * @return resource|null
     */
    public function getHandle(string $filePath) {
        if(array_key_exists($filePath, $this->locks)) {
            return $this->locks[$filePath];
        }

        return null;
    }
}

?>